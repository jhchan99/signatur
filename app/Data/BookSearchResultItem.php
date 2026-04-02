<?php

namespace App\Data;

use App\Models\Work;
use App\Services\OpenLibrary\OpenLibraryBookNormalizer;
use App\Services\OpenLibrary\OpenLibraryWorkSyncService;

final readonly class BookSearchResultItem
{
    public function __construct(
        public string $title,
        public ?string $author,
        public ?int $publishYear,
        public ?string $coverUrl,
        public ?string $openLibraryId,
        public string $source,
        public ?string $detailUrl,
    ) {}

    public static function fromWork(Work $work): self
    {
        return new self(
            title: $work->title,
            author: $work->displayAuthor(),
            publishYear: $work->first_publish_year,
            coverUrl: $work->cover_url,
            openLibraryId: $work->open_library_key,
            source: 'local',
            detailUrl: route('books.show', $work, absolute: false),
        );
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function fromOpenLibrarySearchDoc(
        array $doc,
    ): ?self {
        $key = $doc['key'] ?? null;
        if (! is_string($key) || ! str_starts_with($key, '/works/')) {
            return null;
        }

        $normalized = OpenLibraryWorkSyncService::normalizeWorkKey($key);

        $title = $doc['title'] ?? null;
        if (! is_string($title) || $title === '') {
            $title = 'Unknown title';
        }

        $yearRaw = $doc['first_publish_year'] ?? null;
        $year = is_numeric($yearRaw) ? (int) $yearRaw : null;

        $path = ltrim($normalized, '/');

        return new self(
            title: $title,
            author: OpenLibraryBookNormalizer::authorLabelFromSearchDoc($doc),
            publishYear: $year,
            coverUrl: OpenLibraryBookNormalizer::coverUrlFromSearchDoc($doc),
            openLibraryId: $normalized,
            source: 'open_library',
            detailUrl: 'https://openlibrary.org/'.$path,
        );
    }
}
