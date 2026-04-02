<?php

namespace App\Services\OpenLibrary;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class OpenLibraryBookSyncService
{
    public function __construct(
        protected OpenLibraryService $openLibrary,
    ) {}

    /**
     * Fetch a full work by Open Library work key, resolve authors, and upsert into the catalog.
     */
    public function syncFromWorkKey(string $workKey): ?Book
    {
        $normalizedKey = self::normalizeWorkKey($workKey);
        $work = $this->openLibrary->getWork($normalizedKey);
        if ($work === []) {
            return null;
        }

        $title = $work['title'] ?? null;
        $title = is_string($title) && $title !== '' ? $title : 'Unknown title';

        $yearRaw = $work['first_publish_year'] ?? null;
        $year = is_numeric($yearRaw) ? (int) $yearRaw : null;

        $book = Book::query()->firstOrNew([
            'open_library_id' => $normalizedKey,
        ]);

        $book->fill([
            'title' => $title,
            'cover_url' => OpenLibraryBookNormalizer::coverUrlFromWork($work),
            'publish_year' => $year,
            'description' => OpenLibraryBookNormalizer::description($work['description'] ?? null),
            'subjects' => OpenLibraryBookNormalizer::subjectsFromWork($work),
        ]);
        $book->save();

        $this->syncAuthors($book, $work);

        return $book->load('authors');
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
     * @param  array<string, mixed>  $work
     * @return Collection<int, Author>
     */
    protected function syncAuthors(Book $book, array $work): Collection
    {
        $keys = OpenLibraryBookNormalizer::authorKeysFromWork($work)->take(5)->values();
        if ($keys->isEmpty()) {
            $book->authors()->sync([]);

            return collect();
        }

        $syncPayload = [];
        $resolvedAuthors = collect();

        foreach ($keys as $index => $authorKey) {
            $author = $this->resolveAuthor($authorKey);
            if ($author === null) {
                continue;
            }

            $syncPayload[$author->getKey()] = [
                'position' => $index + 1,
            ];
            $resolvedAuthors->push($author);
        }

        if ($syncPayload !== []) {
            $book->authors()->sync($syncPayload);
        }

        return $resolvedAuthors->values();
    }

    protected function resolveAuthor(string $authorKey): ?Author
    {
        $normalizedAuthorKey = self::normalizeAuthorKey($authorKey);

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
                ],
            );
        }

        return Author::query()
            ->where('open_library_id', $normalizedAuthorKey)
            ->first();
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
