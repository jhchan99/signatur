<?php

namespace App\Services\Books;

use App\Models\BookFeaturedEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class HomepageFeaturedBooksService
{
    /**
     * @return array{heroBook: array{title: string, author: string|null, image: string, href: string}, featuredCovers: list<array{title: string, image: string, href: string}>}
     */
    public function featuredPayload(): array
    {
        /** @var Collection<int, BookFeaturedEntry> $entries */
        $entries = BookFeaturedEntry::query()
            ->with('book')
            ->forLatestImport()
            ->orderBy('position')
            ->get();

        if ($entries->isEmpty()) {
            return self::fallbackPayload();
        }

        return Cache::remember('home.featured_books', (int) config('books.featured.cache_ttl'), function () {
            /** @var Collection<int, BookFeaturedEntry> $fresh */
            $fresh = BookFeaturedEntry::query()
                ->with('book')
                ->forLatestImport()
                ->orderBy('position')
                ->get();

            return self::entriesToPayload($fresh);
        });
    }

    /**
     * @param  Collection<int, BookFeaturedEntry>  $entries
     * @return array{heroBook: array{title: string, author: string|null, image: string, href: string}, featuredCovers: list<array{title: string, image: string, href: string}>}
     */
    protected static function entriesToPayload(Collection $entries): array
    {
        $fallbackImages = self::fallbackCoverImages();

        /** @var list<array{title: string, image: string, href: string}> $covers */
        $covers = [];
        foreach ($entries as $index => $entry) {
            $book = $entry->book;
            $covers[] = [
                'title' => $book->title,
                'image' => $book->cover_url ?? $fallbackImages[$index % count($fallbackImages)],
                'href' => 'https://openlibrary.org'.$book->open_library_id,
            ];
        }

        $hero = $entries->first()?->book;
        if ($hero === null) {
            return self::fallbackPayload();
        }

        return [
            'heroBook' => [
                'title' => $hero->title,
                'author' => $hero->author,
                'image' => $hero->cover_url ?? $fallbackImages[0],
                'href' => 'https://openlibrary.org'.$hero->open_library_id,
            ],
            'featuredCovers' => $covers,
        ];
    }

    /**
     * @return array{heroBook: array{title: string, author: string, image: string, href: string}, featuredCovers: list<array{title: string, image: string, href: string}>}
     */
    public static function fallbackPayload(): array
    {
        $featuredCovers = [
            [
                'title' => 'Project Hail Mary',
                'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Project+Hail+Mary',
            ],
            [
                'title' => 'Tomorrow, and Tomorrow, and Tomorrow',
                'image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Tomorrow%2C+and+Tomorrow%2C+and+Tomorrow',
            ],
            [
                'title' => 'The Seven Husbands of Evelyn Hugo',
                'image' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=The+Seven+Husbands+of+Evelyn+Hugo',
            ],
            [
                'title' => 'The Night Circus',
                'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=The+Night+Circus',
            ],
            [
                'title' => 'Yellowface',
                'image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Yellowface',
            ],
            [
                'title' => 'Piranesi',
                'image' => 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Piranesi',
            ],
        ];

        return [
            'heroBook' => [
                'title' => 'Project Hail Mary',
                'author' => 'Andy Weir',
                'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=1600&q=80',
                'href' => 'https://openlibrary.org/search?q=Project+Hail+Mary',
            ],
            'featuredCovers' => $featuredCovers,
        ];
    }

    /**
     * @return list<string>
     */
    protected static function fallbackCoverImages(): array
    {
        return array_column(self::fallbackPayload()['featuredCovers'], 'image');
    }
}
