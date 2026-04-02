<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

trait SeedsFromCsv
{
    /**
     * @param  callable(array<string, string>): void  $onRow
     */
    protected function eachCsvRow(string $relativePath, callable $onRow): void
    {
        $path = database_path('data/'.$relativePath);

        if (! is_readable($path)) {
            throw new RuntimeException("CSV not found or not readable: {$path}");
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Could not open CSV: {$path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false || $headers === [null] || $headers === ['']) {
                throw new InvalidArgumentException("CSV missing header row: {$path}");
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

                $onRow($data);
            }
        } finally {
            fclose($handle);
        }
    }

    protected function syncPostgresIdSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $allowedTable = match ($table) {
            'users',
            'authors',
            'books',
            'reading_logs',
            'activities',
            'book_featured_entries' => $table,
            default => throw new InvalidArgumentException("Unknown table for Postgres sequence sync: {$table}"),
        };

        $maxId = DB::table($allowedTable)->max('id');

        if ($maxId === null) {
            return;
        }

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('{$allowedTable}', 'id'), ?)",
            [(int) $maxId]
        );
    }
}
