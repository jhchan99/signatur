<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookFeaturedEntry;
use App\Services\OpenLibrary\OpenLibraryBookNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class HomepageFeaturedBooksService
{
    /**
     * @return array{
     *     heroBook: array{
     *         title: string,
     *         author: string|null,
     *         href: string,
     *         image: string,
     *         card_image: string,
     *         external: bool,
     *     },
     *     featuredCovers: list<array{title: string, image: string, href: string, card_image: string, external: bool}>
     * }
     */
    public function featuredPayload(): array
    {
        /** @var Collection<int, BookFeaturedEntry> $entries */
        $entries = BookFeaturedEntry::query()
            ->with('book.authors')
            ->forLatestImport()
            ->orderBy('position')
            ->get();

        if ($entries->isEmpty()) {
            return self::fallbackPayload();
        }

        return Cache::remember('home.featured_books', (int) config('books.featured.cache_ttl'), function () {
            /** @var Collection<int, BookFeaturedEntry> $fresh */
            $fresh = BookFeaturedEntry::query()
                ->with('book.authors')
                ->forLatestImport()
                ->orderBy('position')
                ->get();

            return self::entriesToPayload($fresh);
        });
    }

    /**
     * @param  Collection<int, BookFeaturedEntry>  $entries
     * @return array{
     *     heroBook: array{title: string, author: string|null, href: string, image: string, card_image: string, external: bool},
     *     featuredCovers: list<array{title: string, image: string, href: string, card_image: string, external: bool}>
     * }
     */
    protected static function entriesToPayload(Collection $entries): array
    {
        $fallback = self::fallbackPayload();
        $fallbackImages = self::fallbackCoverImages();
        $fallbackHeroImage = $fallback['heroBook']['image'];

        /** @var list<array{title: string, image: string, href: string, card_image: string, external: bool}> $covers */
        $covers = [];
        foreach ($entries as $index => $entry) {
            $book = $entry->book;
            $card = $book->cover_url ?? $fallbackImages[$index % count($fallbackImages)];
            if ($book->cover_url === null || $book->cover_url === '') {
                $card = $fallbackHeroImage;
            }
            $display = OpenLibraryBookNormalizer::heroCoverUrlFromStoredCover($book->cover_url) ?? $card;
            $covers[] = [
                'title' => $book->title,
                'image' => $display,
                'card_image' => $card,
                'href' => route('books.show', $book),
                'external' => false,
            ];
        }

        $hero = $entries->first()?->book;
        if ($hero === null) {
            return $fallback;
        }

        $heroCard = $hero->cover_url ?? $fallbackImages[0];
        $heroImage = self::resolveHeroBackgroundUrl($hero) ?? $fallbackHeroImage;

        return [
            'heroBook' => [
                'title' => $hero->title,
                'author' => $hero->displayAuthor(),
                'href' => route('books.show', $hero),
                'image' => $heroImage,
                'card_image' => $heroCard,
                'external' => false,
            ],
            'featuredCovers' => $covers,
        ];
    }

    protected static function resolveHeroBackgroundUrl(Book $book): ?string
    {
        $large = OpenLibraryBookNormalizer::heroCoverUrlFromStoredCover($book->cover_url);

        if ($large !== null) {
            return $large;
        }

        return null;
    }

    /**
     * @return array{
     *     heroBook: array{title: string, author: string, href: string, image: string, card_image: string, external: bool},
     *     featuredCovers: list<array{title: string, image: string, href: string, card_image: string, external: bool}>
     * }
     */
    public static function fallbackPayload(): array
    {
        $featuredCovers = [
            [
                'title' => 'Project Hail Mary',
                'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=80',
                'card_image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Project+Hail+Mary',
                'external' => true,
            ],
            [
                'title' => 'Tomorrow, and Tomorrow, and Tomorrow',
                'image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=700&q=80',
                'card_image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Tomorrow%2C+and+Tomorrow%2C+and+Tomorrow',
                'external' => true,
            ],
            [
                'title' => 'The Seven Husbands of Evelyn Hugo',
                'image' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=700&q=80',
                'card_image' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=The+Seven+Husbands+of+Evelyn+Hugo',
                'external' => true,
            ],
            [
                'title' => 'The Night Circus',
                'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=700&q=80',
                'card_image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=The+Night+Circus',
                'external' => true,
            ],
            [
                'title' => 'Yellowface',
                'image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=700&q=80',
                'card_image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Yellowface',
                'external' => true,
            ],
            [
                'title' => 'Piranesi',
                'image' => 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&w=700&q=80',
                'card_image' => 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Piranesi',
                'external' => true,
            ],
        ];

        $heroUnsplash = 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=2400&q=85';

        return [
            'heroBook' => [
                'title' => 'Project Hail Mary',
                'author' => 'Andy Weir',
                'image' => $heroUnsplash,
                'card_image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=80',
                'href' => 'https://openlibrary.org/search?q=Project+Hail+Mary',
                'external' => true,
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
