<?php

namespace Database\Seeders;

use App\Models\Author;
use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookSeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('books.csv', function (array $data): void {
            DB::table('books')->insert([
                'id' => (int) $data['id'],
                'open_library_id' => $data['open_library_id'],
                'title' => $data['title'],
                'cover_url' => ($data['cover_url'] ?? '') === '' ? null : $data['cover_url'], // nullable
                'publish_year' => ($data['publish_year'] ?? '') === '' ? null : (int) $data['publish_year'], // nullable
                'description' => ($data['description'] ?? '') === '' ? null : $data['description'], // nullable
                'subjects' => ($data['subjects'] ?? '') === '' ? null : $data['subjects'], // nullable json
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);

            $authorName = trim((string) ($data['author'] ?? ''));
            if ($authorName !== '') {
                $author = Author::query()->where('name', $authorName)->first()
                    ?? Author::query()->firstOrCreate(
                        ['open_library_id' => '/authors/csv-'.sha1($authorName)],
                        ['name' => $authorName, 'bio' => null],
                    );

                DB::table('book_author')->insert([
                    'book_id' => (int) $data['id'],
                    'author_id' => $author->getKey(),
                    'position' => 1,
                ]);
            }
        });

        $this->syncPostgresIdSequence('books');
    }
}
