<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('activities.csv', function (array $data): void {
            DB::table('activities')->insert([
                'id' => (int) $data['id'],
                'user_id' => (int) $data['user_id'],
                'action_type' => $data['action_type'],
                'target_id' => (int) $data['target_id'],
                'target_type' => $data['target_type'],
                'created_at' => $data['created_at'],
            ]);
        });

        $this->syncPostgresIdSequence('activities');
    }
}
