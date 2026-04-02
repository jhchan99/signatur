<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookFeaturedEntrySeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('book_featured_entries.csv', function (array $data): void {
            DB::table('book_featured_entries')->insert([
                'id' => (int) $data['id'],
                'import_batch' => $data['import_batch'],
                'book_id' => (int) $data['book_id'],
                'position' => (int) $data['position'],
                'source' => $data['source'],
                'list_name' => ($data['list_name'] ?? '') === '' ? null : $data['list_name'], // nullable
                'payload' => ($data['payload'] ?? '') === '' ? null : $data['payload'], // nullable json
                'imported_at' => $data['imported_at'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);
        });

        $this->syncPostgresIdSequence('book_featured_entries');
    }
}
