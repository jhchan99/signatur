<?php

namespace App\Services\OpenLibrary;

use App\Models\Author;
use App\Models\Work;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class OpenLibraryWorkSyncService
{
    public function __construct(
        protected OpenLibraryService $openLibrary,
    ) {}

    /**
     * Fetch a full work by Open Library work key, resolve authors, and upsert into the catalog.
     */
    public function syncFromWorkKey(string $workKey): ?Work
    {
        $normalizedKey = self::normalizeWorkKey($workKey);
        $workPayload = $this->openLibrary->getWork($normalizedKey);
        if ($workPayload === []) {
            return null;
        }

        $title = $workPayload['title'] ?? null;
        $title = is_string($title) && $title !== '' ? $title : 'Unknown title';

        $subtitle = $workPayload['subtitle'] ?? null;
        $subtitle = is_string($subtitle) && $subtitle !== '' ? $subtitle : null;

        $catalogWork = Work::query()->firstOrNew([
            'open_library_key' => $normalizedKey,
        ]);

        $coverId = OpenLibraryBookNormalizer::firstCoverIdFromWork($workPayload);

        $catalogWork->fill([
            'title' => $title,
            'subtitle' => $subtitle,
            'cover_id' => $coverId,
            'first_publish_year' => OpenLibraryBookNormalizer::firstPublishYearFromWork($workPayload),
            'description' => OpenLibraryBookNormalizer::description($workPayload['description'] ?? null),
            'subjects' => OpenLibraryBookNormalizer::subjectsFromWork($workPayload),
        ]);
        $catalogWork->save();

        $this->syncAuthors($catalogWork, $workPayload);

        return $catalogWork->load('authors');
    }

    public static function normalizeWorkKey(string $workKey): string
    {
        $trimmed = trim($workKey);

        if (str_starts_with($trimmed, '/works/')) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, 'works/')) {
            return '/'.$trimmed;
        }

        return '/works/'.ltrim($trimmed, '/');
    }

    /**
     * @param  array<string, mixed>  $workPayload
     * @return Collection<int, Author>
     */
    protected function syncAuthors(Work $work, array $workPayload): Collection
    {
        $authors = $workPayload['authors'] ?? [];
        if (! is_array($authors) || $authors === []) {
            $work->authors()->sync([]);

            return collect();
        }

        $syncPayload = [];
        $resolvedAuthors = collect();
        $position = 1;

        foreach ($authors as $item) {
            if ($position > 5) {
                break;
            }
            if (! is_array($item)) {
                continue;
            }

            $author = $item['author'] ?? null;
            if (! is_array($author) || ! isset($author['key']) || ! is_string($author['key'])) {
                continue;
            }

            $authorModel = $this->resolveAuthor($author['key']);
            if ($authorModel === null) {
                continue;
            }

            $authorId = $authorModel->getKey();
            if (! is_numeric($authorId) || (int) $authorId < 1) {
                continue;
            }

            $role = OpenLibraryBookNormalizer::authorRoleFromWorkAuthorEntry($item);

            $syncPayload[(int) $authorId] = [
                'position' => $position,
                'role' => $role,
            ];
            $resolvedAuthors->push($authorModel);
            $position++;
        }

        if ($syncPayload !== []) {
            $work->authors()->sync($syncPayload);
        } else {
            $work->authors()->sync([]);
        }

        return $resolvedAuthors->values();
    }

    protected function resolveAuthor(string $authorKey): ?Author
    {
        $trimmedKey = trim($authorKey);
        if ($trimmedKey === '') {
            return null;
        }

        $normalizedAuthorKey = self::normalizeAuthorKey($trimmedKey);
        if (! self::isValidNormalizedOpenLibraryAuthorKey($normalizedAuthorKey)) {
            return null;
        }

        try {
            $payload = $this->openLibrary->getAuthor($normalizedAuthorKey);
        } catch (ConnectionException) {
            $payload = [];
        }

        if ($payload !== [] && isset($payload['name']) && is_string($payload['name']) && $payload['name'] !== '') {
            return Author::query()->updateOrCreate(
                ['open_library_id' => $normalizedAuthorKey],
                [
                    'name' => $payload['name'],
                    'bio' => OpenLibraryBookNormalizer::description($payload['bio'] ?? null),
                    'birth_date' => $this->stringOrNull($payload['birth_date'] ?? null, 128),
                    'death_date' => $this->stringOrNull($payload['death_date'] ?? null, 128),
                    'wikipedia' => $this->stringOrNull($payload['wikipedia'] ?? null, 512),
                    'alternate_names' => $this->alternateNamesFromAuthorPayload($payload),
                ],
            );
        }

        return Author::query()->firstOrCreate(
            ['open_library_id' => $normalizedAuthorKey],
            [
                'name' => 'Pending Author',
                'bio' => null,
                'birth_date' => null,
                'death_date' => null,
                'wikipedia' => null,
                'alternate_names' => null,
            ],
        );
    }

    /**
     * Reject keys that normalize to a bare `/authors/` path with no Open Library author id.
     */
    protected static function isValidNormalizedOpenLibraryAuthorKey(string $normalizedKey): bool
    {
        $prefix = '/authors/';

        if (! str_starts_with($normalizedKey, $prefix)) {
            return false;
        }

        $slug = substr($normalizedKey, strlen($prefix));

        return $slug !== '' && trim($slug) !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>|null
     */
    protected function alternateNamesFromAuthorPayload(array $payload): ?array
    {
        $raw = $payload['alternate_names'] ?? null;
        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $names = [];
        foreach ($raw as $item) {
            if (is_string($item) && $item !== '') {
                $names[] = trim($item);
            }
        }

        if ($names === []) {
            return null;
        }

        return array_values(array_unique($names, SORT_STRING));
    }

    protected function stringOrNull(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $maxLength);
    }

    public static function normalizeAuthorKey(string $authorKey): string
    {
        $trimmed = trim($authorKey);

        if (str_starts_with($trimmed, '/authors/')) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, 'authors/')) {
            return '/'.$trimmed;
        }

        return '/authors/'.ltrim($trimmed, '/');
    }
}
