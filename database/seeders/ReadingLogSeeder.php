<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReadingLogSeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('reading_logs.csv', function (array $data): void {
            DB::table('reading_logs')->insert([
                'id' => (int) $data['id'],
                'user_id' => (int) $data['user_id'],
                'work_id' => (int) $data['work_id'],
                'status' => $data['status'],
                'rating' => ($data['rating'] ?? '') === '' ? null : $data['rating'], // nullable
                'review_text' => ($data['review_text'] ?? '') === '' ? null : $data['review_text'], // nullable
                'is_spoiler' => filter_var($data['is_spoiler'], FILTER_VALIDATE_BOOLEAN),
                'is_private' => filter_var($data['is_private'], FILTER_VALIDATE_BOOLEAN),
                'date_started' => ($data['date_started'] ?? '') === '' ? null : $data['date_started'], // nullable
                'date_finished' => ($data['date_finished'] ?? '') === '' ? null : $data['date_finished'], // nullable
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);
        });

        $this->syncPostgresIdSequence('reading_logs');
    }
}
