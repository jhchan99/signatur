<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowSeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('follows.csv', function (array $data): void {
            DB::table('follows')->insert([
                'followee_id' => (int) $data['followee_id'],
                'follower_id' => (int) $data['follower_id'],
                'created_at' => $data['created_at'],
            ]);
        });
    }
}
