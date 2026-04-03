<?php

namespace App\Services\OpenLibrary;

use App\Models\Edition;
use App\Models\Work;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OpenLibraryDumpImportService
{
    public const string TYPE_AUTHOR = '/type/author';

    public const string TYPE_WORK = '/type/work';

    public const string TYPE_EDITION = '/type/edition';

    /**
     * @return \Generator<int, string>
     */
    public function lines(string $absolutePath): \Generator
    {
        if (! is_readable($absolutePath)) {
            throw new InvalidArgumentException('Dump file is not readable: '.$absolutePath);
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException('Unable to open dump file: '.$absolutePath);
        }

        try {
            while (($line = fgets($handle)) !== false) {
                yield rtrim($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{type_key: string, ol_key: string, revision: string, last_modified: string, data: array<string, mixed>}|null
     */
    public function parseLine(string $line): ?array
    {
        if ($line === '') {
            return null;
        }

        $parts = explode("\t", $line, 5);
        if (count($parts) < 5) {
            return null;
        }

        $decoded = json_decode($parts[4], true);
        if (! is_array($decoded)) {
            return null;
        }

        return [
            'type_key' => $parts[0],
            'ol_key' => $parts[1],
            'revision' => $parts[2],
            'last_modified' => $parts[3],
            'data' => $decoded,
        ];
    }

    /**
     * @return \Generator<int, array{type_key: string, ol_key: string, revision: string, last_modified: string, data: array<string, mixed>}>
     */
    public function eachParsedRowOfType(string $absolutePath, string $expectedType): \Generator
    {
        $path = realpath($absolutePath) ?: $absolutePath;

        foreach ($this->lines($path) as $line) {
            $parsed = $this->parseLine($line);
            if ($parsed === null || $parsed['type_key'] !== $expectedType) {
                continue;
            }

            yield $parsed;
        }
    }

    /**
     * @param  array{type_key: string, ol_key: string, revision: string, last_modified: string, data: array<string, mixed>}  $parsed
     * @return array<string, mixed>|null
     */
    public function buildAuthorUpsertRow(array $parsed): ?array
    {
        $data = $parsed['data'];
        $key = OpenLibraryWorkSyncService::normalizeAuthorKey($parsed['ol_key']);
        $name = $data['name'] ?? null;
        if (! is_string($name)) {
            return null;
        }

        $name = trim($name);
        if ($name === '' || ! $this->isImportableAuthorName($name)) {
            return null;
        }

        $alternate = $data['alternate_names'] ?? null;
        /** @var list<string>|null $alternateNames */
        $alternateNames = null;
        if (is_array($alternate) && $alternate !== []) {
            $names = [];
            foreach ($alternate as $item) {
                if (is_string($item) && $item !== '') {
                    $names[] = trim($item);
                }
            }
            $alternateNames = $names === [] ? null : array_values(array_unique($names, SORT_STRING));
        }

        return [
            'open_library_id' => $key,
            'name' => $name,
            'bio' => OpenLibraryBookNormalizer::description($data['bio'] ?? null),
            'birth_date' => $this->normalizeAuthorLifeYear($data['birth_date'] ?? null),
            'death_date' => $this->normalizeAuthorLifeYear($data['death_date'] ?? null),
            'wikipedia' => $this->truncateString($data['wikipedia'] ?? null, 512),
            'alternate_names' => $alternateNames === null ? null : json_encode($alternateNames),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Strict catalog policy: keep only ASCII names that look like normal English personal names.
     * Rows with wrapper punctuation, HTML entities, title-like text, or label-like ALL CAPS tokens are dropped.
     */
    protected function isImportableAuthorName(string $name): bool
    {
        return $this->openLibraryImportTextIsAsciiOnly($name)
            && ! $this->openLibraryImportTextHasHtmlEntityPattern($name)
            && $this->authorNameLengthIsWithinLimit($name, 64)
            && $this->authorNameStartsAndEndsWithAsciiLetter($name)
            && ! $this->authorNameContainsForbiddenCharacters($name)
            && $this->authorNameWordsPassEnglishPersonalNameRules($name);
    }

    protected function openLibraryImportTextIsAsciiOnly(string $value): bool
    {
        return ! preg_match('/[^\x00-\x7F]/', $value);
    }

    protected function openLibraryImportTextHasHtmlEntityPattern(string $value): bool
    {
        return preg_match('/&#\d+;|&[a-zA-Z][a-zA-Z0-9]{0,48};/', $value) === 1;
    }

    protected function authorNameLengthIsWithinLimit(string $name, int $maxBytes): bool
    {
        return strlen($name) <= $maxBytes;
    }

    protected function authorNameStartsAndEndsWithAsciiLetter(string $name): bool
    {
        return preg_match('/^[A-Za-z]/', $name) === 1 && preg_match('/[A-Za-z]$/', $name) === 1;
    }

    protected function authorNameContainsForbiddenCharacters(string $name): bool
    {
        return preg_match('/[0-9&,";:()[\]{}\/\\\\|`!@#$%*+=?<>]/', $name) === 1;
    }

    /**
     * @return bool false when word count or per-token shape fails English personal-name heuristics.
     */
    protected function authorNameWordsPassEnglishPersonalNameRules(string $name): bool
    {
        /** @var list<string>|false $words */
        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return false;
        }

        if (count($words) > 6) {
            return false;
        }

        foreach ($words as $word) {
            if (strlen($word) > 64) {
                return false;
            }

            if (preg_match('/^[A-Z]{2,}$/', $word) === 1) {
                return false;
            }

            if (! $this->isEnglishLookingAuthorWord($word)) {
                return false;
            }
        }

        return true;
    }

    /**
     * English-looking book titles only: ASCII, no entity spam, no bibliography/conference markers, conservative length and word count.
     */
    protected function isImportableWorkTitle(string $title): bool
    {
        $title = trim($title);
        if ($title === '' || strlen($title) < 2) {
            return false;
        }

        if (! $this->openLibraryImportTextIsAsciiOnly($title)) {
            return false;
        }

        if ($this->openLibraryImportTextHasHtmlEntityPattern($title)) {
            return false;
        }

        if (strlen($title) > 200) {
            return false;
        }

        if (! ctype_alpha($title[0])) {
            return false;
        }

        $lastIndex = strlen($title) - 1;
        $last = $title[$lastIndex];
        if (! ctype_alnum($last) && ! in_array($last, ['!', '?', '\'', ')', ']'], true)) {
            return false;
        }

        if (in_array($last, [',', ':', ';', '-', '.'], true)) {
            return false;
        }

        if (preg_match('/^[(\[]/', $title) === 1 || preg_match('/[)\]]$/', $title) === 1) {
            return false;
        }

        if (preg_match('/^\([^)]+\)$/', $title) === 1) {
            return false;
        }

        if (preg_match("/[^A-Za-z0-9\\s\\-':,.!?()\\[\\]\"]/", $title) === 1) {
            return false;
        }

        if (str_contains($title, ',,')) {
            return false;
        }

        if (preg_match('/\b(proceedings|symposium|conference|congress|workshop|arxiv|http|https|www\.|doi:|isbn)\b/i', $title) === 1) {
            return false;
        }

        /** @var list<string>|false $words */
        $words = preg_split('/\s+/', $title, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === [] || count($words) > 24) {
            return false;
        }

        $lettersOnly = preg_replace('/[^A-Za-z]/', '', $title) ?? '';
        if ($lettersOnly !== '' && $lettersOnly === strtoupper($lettersOnly) && strlen($lettersOnly) > 15) {
            return false;
        }

        return true;
    }

    protected function isEnglishLookingAuthorWord(string $word): bool
    {
        if (preg_match('/^([A-Z]\.)+$/', $word)) {
            return true;
        }

        if (preg_match('/^[A-Z][a-z]+$/', $word)) {
            return true;
        }

        if (preg_match('/^[A-Z][a-z]+-[A-Z][a-z]+$/', $word)) {
            return true;
        }

        if (preg_match('/^[A-Z][a-z]+\'[A-Z][a-z]+$/', $word)) {
            return true;
        }

        if (preg_match('/^[A-Z]\'[A-Z][a-z]+$/', $word)) {
            return true;
        }

        if (preg_match('/^Mc[A-Z][a-z]+$/', $word)) {
            return true;
        }

        if (preg_match('/^Mac[A-Z][a-z]+$/', $word)) {
            return true;
        }

        return false;
    }

    /**
     * Reduce messy Open Library life-date strings to a single 4-digit year, or null.
     */
    protected function normalizeAuthorLifeYear(mixed $raw): ?string
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match_all('/\b(1[0-9]{3}|20[0-9]{2}|2100)\b/', $trimmed, $matches) !== 0) {
            foreach ($matches[1] as $candidate) {
                $year = (int) $candidate;
                if ($year >= 1000 && $year <= 2100) {
                    return (string) $year;
                }
            }
        }

        return null;
    }

    /**
     * @param  array{type_key: string, ol_key: string, revision: string, last_modified: string, data: array<string, mixed>}  $parsed
     * @return array{work_row: array<string, mixed>, author_links: list<array{work_key: string, author_key: string, position: int, role: string|null}>}|null
     */
    public function buildWorkBatchItem(array $parsed): ?array
    {
        $data = $parsed['data'];
        $key = OpenLibraryWorkSyncService::normalizeWorkKey($parsed['ol_key']);

        $title = $data['title'] ?? null;
        if (! is_string($title)) {
            return null;
        }

        $title = trim($title);
        if ($title === '' || ! $this->isImportableWorkTitle($title)) {
            return null;
        }

        $subtitle = $data['subtitle'] ?? null;
        $subtitle = is_string($subtitle) && $subtitle !== '' ? $subtitle : null;

        return [
            'work_row' => [
                'open_library_key' => $key,
                'title' => $title,
                'subtitle' => $subtitle,
                'cover_id' => OpenLibraryBookNormalizer::firstCoverIdFromWork($data),
                'first_publish_year' => OpenLibraryBookNormalizer::firstPublishYearFromWork($data),
                'description' => OpenLibraryBookNormalizer::description($data['description'] ?? null),
                'subjects' => json_encode(OpenLibraryBookNormalizer::subjectsFromWork($data)),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'author_links' => $this->authorLinksFromWorkPayload($key, $data),
        ];
    }

    /**
     * @param  array{type_key: string, ol_key: string, revision: string, last_modified: string, data: array<string, mixed>}  $parsed
     * @return array{edition_row: array<string, mixed>, isbns: list<string>}|null
     */
    public function buildEditionBatchItem(array $parsed): ?array
    {
        $data = $parsed['data'];
        $key = $this->normalizeEditionKey($parsed['ol_key']);

        $workId = $this->resolveWorkIdFromEditionPayload($data);

        $title = $data['title'] ?? null;
        $title = is_string($title) && $title !== '' ? $title : null;

        $subtitle = $data['subtitle'] ?? null;
        $subtitle = is_string($subtitle) && $subtitle !== '' ? $subtitle : null;

        $byStatement = $data['by_statement'] ?? null;
        $byStatement = is_string($byStatement) && $byStatement !== '' ? $this->truncateString($byStatement, 512) : null;

        $editionName = $data['edition_name'] ?? null;
        $editionName = is_string($editionName) && $editionName !== '' ? $this->truncateString($editionName, 255) : null;

        $physicalFormat = $data['physical_format'] ?? null;
        $physicalFormat = is_string($physicalFormat) && $physicalFormat !== '' ? $this->truncateString($physicalFormat, 255) : null;

        $publishDate = $data['publish_date'] ?? null;
        $publishDate = is_string($publishDate) && $publishDate !== '' ? $this->truncateString($publishDate, 255) : null;

        $pagesRaw = $data['number_of_pages'] ?? null;
        $pages = is_numeric($pagesRaw) ? (int) $pagesRaw : null;

        $publishers = $this->stringListFromPayload($data['publishers'] ?? null);

        $isbn10 = $this->stringListFromPayload($data['isbn_10'] ?? null);
        $isbn13 = $this->stringListFromPayload($data['isbn_13'] ?? null);
        $isbns = [];
        foreach (array_merge($isbn10, $isbn13) as $isbn) {
            $normalized = preg_replace('/[^0-9X]/i', '', $isbn) ?? '';
            if ($normalized !== '') {
                $isbns[$normalized] = true;
            }
        }
        $isbns = array_keys($isbns);

        return [
            'edition_row' => [
                'open_library_key' => $key,
                'work_id' => $workId,
                'title' => $title,
                'subtitle' => $subtitle,
                'by_statement' => $byStatement,
                'edition_name' => $editionName,
                'physical_format' => $physicalFormat,
                'publishers' => $publishers === [] ? null : json_encode($publishers),
                'publish_date' => $publishDate,
                'number_of_pages' => $pages,
                'cover_id' => OpenLibraryBookNormalizer::firstCoverIdFromWork($data),
                'languages' => json_encode(OpenLibraryBookNormalizer::languageCodesFromEdition($data)),
                'subjects' => json_encode(OpenLibraryBookNormalizer::subjectsFromWork($data)),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'isbns' => $isbns,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{work_key: string, author_key: string, position: int, role: string|null}>
     */
    protected function authorLinksFromWorkPayload(string $workKey, array $data): array
    {
        $authors = $data['authors'] ?? [];
        if (! is_array($authors) || $authors === []) {
            return [];
        }

        $links = [];
        $position = 1;
        foreach ($authors as $item) {
            if (! is_array($item)) {
                continue;
            }
            $author = $item['author'] ?? null;
            if (! is_array($author) || ! isset($author['key']) || ! is_string($author['key'])) {
                continue;
            }
            $role = OpenLibraryBookNormalizer::authorRoleFromWorkAuthorEntry($item);
            $links[] = [
                'work_key' => $workKey,
                'author_key' => OpenLibraryWorkSyncService::normalizeAuthorKey($author['key']),
                'position' => $position,
                'role' => $role,
            ];
            $position++;
        }

        return $links;
    }

    protected function normalizeEditionKey(string $key): string
    {
        $trimmed = trim($key);
        if (str_starts_with($trimmed, '/books/')) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, 'books/')) {
            return '/'.$trimmed;
        }

        return '/books/'.ltrim($trimmed, '/');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveWorkIdFromEditionPayload(array $data): ?int
    {
        $works = $data['works'] ?? [];
        if (! is_array($works) || $works === []) {
            return null;
        }
        $first = $works[0] ?? null;
        if (! is_array($first)) {
            return null;
        }
        $wk = $first['key'] ?? null;
        if (! is_string($wk) || $wk === '') {
            return null;
        }

        $normalized = OpenLibraryWorkSyncService::normalizeWorkKey($wk);

        return Work::query()->where('open_library_key', $normalized)->value('id');
    }

    /**
     * @return ($value is null ? null : string)
     */
    protected function truncateString(mixed $value, int $max): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }

    /**
     * @return list<string>
     */
    protected function stringListFromPayload(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out, SORT_STRING));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertAuthorChunk(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table('authors')->upsert(
            $rows,
            ['open_library_id'],
            ['name', 'bio', 'birth_date', 'death_date', 'wikipedia', 'alternate_names', 'updated_at'],
        );
    }

    /**
     * Work dump rows reference author keys before (or instead of) a passing authors-dump row.
     * Insert placeholder authors so {@see flushWorksBatch()} can attach author_works rows;
     * a later {@see upsertAuthorChunk()} replaces the stub with real metadata.
     *
     * @param  list<string>  $authorKeys  Normalized Open Library author keys (e.g. /authors/OL1A)
     */
    protected function ensureStubAuthorsForReferencedKeys(array $authorKeys): void
    {
        if ($authorKeys === []) {
            return;
        }

        /** @var list<string> $existing */
        $existing = DB::table('authors')->whereIn('open_library_id', $authorKeys)->pluck('open_library_id')->all();
        $have = array_fill_keys($existing, true);

        $ts = now();
        $stubRows = [];
        foreach ($authorKeys as $key) {
            if (isset($have[$key])) {
                continue;
            }
            $stubRows[] = [
                'open_library_id' => $key,
                'name' => 'Pending Author',
                'bio' => null,
                'birth_date' => null,
                'death_date' => null,
                'wikipedia' => null,
                'alternate_names' => null,
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        if ($stubRows !== []) {
            DB::table('authors')->insert($stubRows);
        }
    }

    /**
     * @param  list<array{work_row: array<string, mixed>, author_links: list<array{work_key: string, author_key: string, position: int, role: string|null}>}>  $batch
     */
    public function flushWorksBatch(array $batch): void
    {
        if ($batch === []) {
            return;
        }

        $workRows = array_map(fn (array $item): array => $item['work_row'], $batch);

        DB::table('works')->upsert(
            $workRows,
            ['open_library_key'],
            ['title', 'subtitle', 'cover_id', 'first_publish_year', 'description', 'subjects', 'updated_at'],
        );

        $keys = array_column($workRows, 'open_library_key');
        /** @var array<string, int> $ids */
        $ids = Work::query()->whereIn('open_library_key', $keys)->pluck('id', 'open_library_key')->all();

        $authorKeys = [];
        foreach ($batch as $item) {
            foreach ($item['author_links'] as $link) {
                $authorKeys[] = $link['author_key'];
            }
        }
        $authorKeys = array_values(array_unique($authorKeys));
        $this->ensureStubAuthorsForReferencedKeys($authorKeys);

        /** @var array<string, int> $authorIds */
        $authorIds = $authorKeys === []
            ? []
            : DB::table('authors')->whereIn('open_library_id', $authorKeys)->pluck('id', 'open_library_id')->all();

        $workIds = array_values($ids);
        if ($workIds !== []) {
            DB::table('author_works')->whereIn('work_id', $workIds)->delete();
        }

        $pivotRows = [];
        foreach ($batch as $item) {
            $wk = $item['work_row']['open_library_key'];
            $workId = $ids[$wk] ?? null;
            if ($workId === null) {
                continue;
            }
            foreach ($item['author_links'] as $link) {
                $authorId = $authorIds[$link['author_key']] ?? null;
                if ($authorId === null) {
                    continue;
                }
                $pivotRows[] = [
                    'work_id' => $workId,
                    'author_id' => $authorId,
                    'position' => $link['position'],
                    'role' => $link['role'],
                ];
            }
        }

        if ($pivotRows !== []) {
            DB::table('author_works')->insert($pivotRows);
        }
    }

    /**
     * @param  list<array{edition_row: array<string, mixed>, isbns: list<string>}>  $batch
     */
    public function flushEditionsBatch(array $batch): void
    {
        if ($batch === []) {
            return;
        }

        $editionRows = array_map(fn (array $item): array => $item['edition_row'], $batch);

        DB::table('editions')->upsert(
            $editionRows,
            ['open_library_key'],
            ['work_id', 'title', 'subtitle', 'by_statement', 'edition_name', 'physical_format', 'publishers', 'publish_date', 'number_of_pages', 'cover_id', 'languages', 'subjects', 'updated_at'],
        );

        $keys = array_column($editionRows, 'open_library_key');
        /** @var array<string, int> $editionIds */
        $editionIds = Edition::query()->whereIn('open_library_key', $keys)->pluck('id', 'open_library_key')->all();

        $ids = array_values($editionIds);
        if ($ids !== []) {
            DB::table('edition_isbns')->whereIn('edition_id', $ids)->delete();
        }

        $isbnRows = [];
        $ts = now();
        foreach ($batch as $item) {
            $ek = $item['edition_row']['open_library_key'];
            $editionId = $editionIds[$ek] ?? null;
            if ($editionId === null) {
                continue;
            }
            foreach ($item['isbns'] as $isbn) {
                $isbnRows[] = [
                    'edition_id' => $editionId,
                    'isbn' => $isbn,
                    'created_at' => $ts,
                    'updated_at' => $ts,
                ];
            }
        }

        if ($isbnRows !== []) {
            DB::table('edition_isbns')->insert($isbnRows);
        }
    }
}
