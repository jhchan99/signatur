<?php

namespace App\Services\Books;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class GoodbooksBootstrapService
{
    use SeedsFromCsv;

    /**
     * @param  null|callable(string): void  $onProgress
     */
    public function bootstrap(string $dataDir, bool $force, ?callable $onProgress = null): int
    {
        $dataDir = realpath($dataDir) ?: $dataDir;
        if (! is_dir($dataDir)) {
            throw new InvalidArgumentException('Goodbooks data directory not found: '.$dataDir);
        }

        $allowedLangs = array_fill_keys(config('goodbooks.allowed_language_codes', []), true);
        $minWorkRatings = (int) config('goodbooks.min_work_ratings_count', 0);
        $maxSubjects = (int) config('goodbooks.max_subjects_per_work', 40);

        if ($allowedLangs === []) {
            throw new InvalidArgumentException('goodbooks.allowed_language_codes must not be empty.');
        }

        $booksPath = $dataDir.'/books.csv';
        $tagsPath = $dataDir.'/tags.csv';
        $bookTagsPath = $dataDir.'/book_tags.csv';

        foreach ([$booksPath, $tagsPath, $bookTagsPath] as $path) {
            if (! is_readable($path)) {
                throw new RuntimeException('Required Goodbooks file missing: '.$path);
            }
        }

        if ($force) {
            $this->truncateCatalogTables();
        } elseif (DB::table('works')->exists()) {
            throw new InvalidArgumentException(
                'The works table is not empty. Run with --force to replace the catalog, or clear works manually.',
            );
        }

        $tagNames = $this->loadTagNamesFromPath($tagsPath);
        $subjectsByGoodreadsBookId = $this->loadSubjectsByGoodreadsBookId($bookTagsPath, $tagNames, $maxSubjects);

        $now = now();
        $importedWorks = 0;

        DB::transaction(function () use (
            $booksPath,
            $allowedLangs,
            $minWorkRatings,
            $subjectsByGoodreadsBookId,
            $now,
            &$importedWorks,
            $onProgress,
        ): void {
            $handle = fopen($booksPath, 'r');
            if ($handle === false) {
                throw new RuntimeException('Could not open books CSV.');
            }

            try {
                $headers = fgetcsv($handle);
                if ($headers === false || $headers === [null]) {
                    throw new InvalidArgumentException('books.csv missing header row.');
                }

                /** @var array<string, int> $authorIdsByDedupeKey dedupe keys from {@see goodbooksAuthorDedupeKey}, not Open Library ids */
                $authorIdsByDedupeKey = [];

                while (($row = fgetcsv($handle)) !== false) {
                    if ($row === [null]) {
                        continue;
                    }
                    if (count($row) !== count($headers)) {
                        continue;
                    }

                    /** @var array<string, string> $data */
                    $data = array_combine($headers, $row);
                    if ($data === false) {
                        continue;
                    }

                    if (! $this->passesImportFilters($data, $allowedLangs, $minWorkRatings)) {
                        continue;
                    }

                    $bookId = (int) $data['book_id'];
                    $goodreadsBookId = (int) $data['goodreads_book_id'];
                    $title = trim((string) $data['title']);
                    $authorsRaw = trim((string) ($data['authors'] ?? ''));

                    if ($title === '' || $authorsRaw === '') {
                        continue;
                    }

                    $authorNames = $this->dedupeAuthorNamesPreservingOrder($this->parseAuthorNames($authorsRaw));
                    if ($authorNames === []) {
                        continue;
                    }

                    $year = $this->parsePublicationYear($data['original_publication_year'] ?? null);
                    $subjectList = $subjectsByGoodreadsBookId[$goodreadsBookId] ?? [];
                    $subjectsJson = $subjectList === [] ? null : json_encode(array_values($subjectList));

                    $authorIdsOrdered = [];

                    foreach ($authorNames as $position => $authorName) {
                        $dedupeKey = $this->goodbooksAuthorDedupeKey($authorName);
                        if (! isset($authorIdsByDedupeKey[$dedupeKey])) {
                            $authorIdsByDedupeKey[$dedupeKey] = (int) DB::table('authors')->insertGetId([
                                'open_library_id' => null,
                                'goodbooks_author_id' => $dedupeKey,
                                'name' => $authorName,
                                'bio' => null,
                                'birth_date' => null,
                                'death_date' => null,
                                'wikipedia' => null,
                                'alternate_names' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                        $authorIdsOrdered[$position + 1] = $authorIdsByDedupeKey[$dedupeKey];
                    }

                    $workId = (int) DB::table('works')->insertGetId([
                        'open_library_key' => null,
                        'goodbooks_book_id' => $bookId,
                        'title' => $title,
                        'subtitle' => null,
                        'cover_id' => null,
                        'first_publish_year' => $year,
                        'description' => null,
                        'subjects' => $subjectsJson,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    foreach ($authorIdsOrdered as $position => $authorId) {
                        DB::table('author_works')->insert([
                            'work_id' => $workId,
                            'author_id' => $authorId,
                            'position' => $position,
                            'role' => null,
                        ]);
                    }

                    $importedWorks++;
                    if ($onProgress !== null && $importedWorks % 500 === 0) {
                        $onProgress('Imported '.$importedWorks.' works…');
                    }
                }
            } finally {
                fclose($handle);
            }
        });

        $this->syncPostgresIdSequence('works');
        $this->syncPostgresIdSequence('authors');

        return $importedWorks;
    }

    /**
     * Stable hash for deduplicating Goodbooks author names. Not stored in the database and not an Open Library id.
     */
    public function goodbooksAuthorDedupeKey(string $authorName): string
    {
        $normalized = mb_strtolower(trim($authorName));

        return hash('sha1', $normalized);
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, true>  $allowedLangs
     */
    protected function passesImportFilters(array $row, array $allowedLangs, int $minWorkRatings): bool
    {
        $lang = trim((string) ($row['language_code'] ?? ''));
        if ($lang === '' || ! isset($allowedLangs[$lang])) {
            return false;
        }

        $workRatings = (int) round((float) ($row['work_ratings_count'] ?? 0));
        if ($workRatings < $minWorkRatings) {
            return false;
        }

        return true;
    }

    protected function parsePublicationYear(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            $y = (int) round((float) $raw);

            return $y >= 1000 && $y <= 2100 ? $y : null;
        }

        if (is_string($raw) && preg_match('/\b(1[0-9]{3}|20[0-9]{2}|2100)\b/', $raw, $m) === 1) {
            $y = (int) $m[1];

            return $y >= 1000 && $y <= 2100 ? $y : null;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function parseAuthorNames(string $authorsField): array
    {
        $parts = array_map(trim(...), explode(',', $authorsField));
        $out = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return array_values($out);
    }

    /**
     * The author_works primary key is (work_id, author_id), so the same author cannot appear twice on one work.
     * Goodbooks sometimes lists an author more than once; keep first occurrence order and spelling.
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    public function dedupeAuthorNamesPreservingOrder(array $names): array
    {
        $seen = [];
        $out = [];
        foreach ($names as $name) {
            $key = $this->goodbooksAuthorDedupeKey($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }

        return $out;
    }

    /**
     * @return array<int, string> tag_id => tag_name
     */
    protected function loadTagNamesFromPath(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not open tags CSV.');
        }

        $map = [];
        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new InvalidArgumentException('tags.csv missing header.');
            }
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || count($row) < 2) {
                    continue;
                }
                $tagId = (int) $row[0];
                $name = trim((string) $row[1]);
                if ($name !== '') {
                    $map[$tagId] = $name;
                }
            }
        } finally {
            fclose($handle);
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $tagNames
     * @return array<int, list<string>> goodreads_book_id => subject strings
     */
    protected function loadSubjectsByGoodreadsBookId(string $path, array $tagNames, int $maxPerBook): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not open book_tags CSV.');
        }

        $out = [];
        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new InvalidArgumentException('book_tags.csv missing header.');
            }
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || count($row) < 2) {
                    continue;
                }
                $grBookId = (int) $row[0];
                $tagId = (int) $row[1];
                if (! isset($tagNames[$tagId])) {
                    continue;
                }
                $tagName = $tagNames[$tagId];
                if ($tagName === '' || $tagName === '-') {
                    continue;
                }
                if (! isset($out[$grBookId])) {
                    $out[$grBookId] = [];
                }
                if (count($out[$grBookId]) >= $maxPerBook) {
                    continue;
                }
                if (in_array($tagName, $out[$grBookId], true)) {
                    continue;
                }
                $out[$grBookId][] = $tagName;
            }
        } finally {
            fclose($handle);
        }

        return $out;
    }

    protected function truncateCatalogTables(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('author_works')->delete();
        DB::table('book_featured_entries')->delete();
        DB::table('reading_logs')->delete();
        DB::table('edition_isbns')->delete();
        DB::table('editions')->delete();
        DB::table('works')->delete();
        DB::table('authors')->delete();

        Schema::enableForeignKeyConstraints();
    }
}
