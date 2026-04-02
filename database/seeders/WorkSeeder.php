<?php

namespace Database\Seeders;

use App\Models\Author;
use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkSeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('works.csv', function (array $data): void {
            DB::table('works')->insert([
                'id' => (int) $data['id'],
                'open_library_key' => $data['open_library_key'],
                'title' => $data['title'],
                'subtitle' => ($data['subtitle'] ?? '') === '' ? null : $data['subtitle'],
                'cover_id' => ($data['cover_id'] ?? '') === '' ? null : (int) $data['cover_id'],
                'first_publish_year' => ($data['first_publish_year'] ?? '') === '' ? null : (int) $data['first_publish_year'],
                'description' => ($data['description'] ?? '') === '' ? null : $data['description'],
                'subjects' => ($data['subjects'] ?? '') === '' ? null : $data['subjects'],
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

                DB::table('author_works')->insert([
                    'work_id' => (int) $data['id'],
                    'author_id' => $author->getKey(),
                    'position' => 1,
                    'role' => null,
                ]);
            }
        });

        $this->syncPostgresIdSequence('works');
    }
}
