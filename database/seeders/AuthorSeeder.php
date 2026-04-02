<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorSeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('authors.csv', function (array $data): void {
            $openLibraryId = trim((string) ($data['open_library_id'] ?? ''));
            if ($openLibraryId === '') {
                return;
            }

            if (! str_starts_with($openLibraryId, '/authors/')) {
                $openLibraryId = '/authors/'.ltrim($openLibraryId, '/');
            }

            DB::table('authors')->insert([
                'id' => (int) $data['id'],
                'open_library_id' => $openLibraryId,
                'name' => $data['name'],
                'bio' => (($data['bio'] ?? '') === '') ? null : $data['bio'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->syncPostgresIdSequence('authors');
    }
}
