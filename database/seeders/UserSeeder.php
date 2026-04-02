<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/users.csv');

        if (! is_readable($path)) {
            throw new RuntimeException("User seed CSV not found or not readable: {$path}");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Could not open user seed CSV: {$path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false || $headers === [null] || $headers === ['']) {
                throw new InvalidArgumentException("User seed CSV is missing a header row: {$path}");
            }

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    continue;
                }

                $data = array_combine($headers, $row);

                if ($data === false) {
                    continue;
                }

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
            }
        } finally {
            fclose($handle);
        }

        $this->syncUserIdSequence();
    }

    private function syncUserIdSequence(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $maxId = DB::table('users')->max('id');

        if ($maxId === null) {
            return;
        }

        DB::statement(
            'SELECT setval(pg_get_serial_sequence(\'users\', \'id\'), ?)',
            [(int) $maxId]
        );
    }
}
