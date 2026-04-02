<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsFromCsv;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    use SeedsFromCsv;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->eachCsvRow('users.csv', function (array $data): void {
            DB::table('users')->insert([
                'id' => (int) $data['id'],
                'name' => $data['name'],
                'username' => ($data['username'] ?? '') === '' ? null : $data['username'], // nullable
                'display_name' => ($data['display_name'] ?? '') === '' ? null : $data['display_name'], // nullable
                'avatar_url' => ($data['avatar_url'] ?? '') === '' ? null : $data['avatar_url'], // nullable
                'email' => $data['email'],
                'email_verified_at' => ($data['email_verified_at'] ?? '') === '' ? null : $data['email_verified_at'], // nullable
                'password' => $data['password'],
                'two_factor_secret' => ($data['two_factor_secret'] ?? '') === '' ? null : $data['two_factor_secret'], // nullable
                'two_factor_recovery_codes' => ($data['two_factor_recovery_codes'] ?? '') === '' ? null : $data['two_factor_recovery_codes'], // nullable
                'two_factor_confirmed_at' => ($data['two_factor_confirmed_at'] ?? '') === '' ? null : $data['two_factor_confirmed_at'], // nullable
                'bio' => ($data['bio'] ?? '') === '' ? null : $data['bio'], // nullable
                'remember_token' => ($data['remember_token'] ?? '') === '' ? null : $data['remember_token'], // nullable
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);
        });

        $this->syncPostgresIdSequence('users');
    }
}
