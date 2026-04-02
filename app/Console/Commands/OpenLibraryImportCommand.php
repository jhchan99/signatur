<?php

namespace App\Console\Commands;

use App\Services\OpenLibrary\OpenLibraryDumpImportService;
use Illuminate\Console\Command;

class OpenLibraryImportCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'openlibrary:import
        {type : authors|works|editions}
        {file : Absolute or relative path to a decompressed ol_dump_*.txt file}
        {--limit= : Maximum number of matching dump rows to import}
        {--chunk=500 : Number of rows per database batch}';

    /**
     * @var string
     */
    protected $description = 'Import Open Library bulk dump rows into the catalog';

    public function handle(OpenLibraryDumpImportService $importer): int
    {
        $type = (string) $this->argument('type');
        $fileArg = (string) $this->argument('file');
        $file = $fileArg;
        if (! is_readable($file) && is_readable(base_path($fileArg))) {
            $file = base_path($fileArg);
        }
        if (! is_readable($file)) {
            $this->error('File not readable: '.$fileArg);

            return self::FAILURE;
        }

        $limitRaw = $this->option('limit');
        $limit = $limitRaw !== null && $limitRaw !== '' ? (int) $limitRaw : null;
        if ($limit !== null && $limit < 1) {
            $this->error('--limit must be a positive integer.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) ($this->option('chunk') ?? 500));

        $processed = match ($type) {
            'authors' => $this->importAuthors($importer, $file, $limit, $chunk),
            'works' => $this->importWorks($importer, $file, $limit, $chunk),
            'editions' => $this->importEditions($importer, $file, $limit, $chunk),
            default => null,
        };

        if ($processed === null) {
            $this->error('Type must be one of: authors, works, editions');

            return self::FAILURE;
        }

        $this->info('Imported '.$processed.' '.$type.' row(s).');

        return self::SUCCESS;
    }

    protected function importAuthors(OpenLibraryDumpImportService $importer, string $file, ?int $limit, int $chunk): int
    {
        $buffer = [];
        $processed = 0;
        foreach ($importer->eachParsedRowOfType($file, OpenLibraryDumpImportService::TYPE_AUTHOR) as $parsed) {
            $row = $importer->buildAuthorUpsertRow($parsed);
            if ($row !== null) {
                $buffer[] = $row;
            }
            $processed++;
            if (count($buffer) >= $chunk) {
                $importer->upsertAuthorChunk($buffer);
                $buffer = [];
            }
            if ($limit !== null && $processed >= $limit) {
                break;
            }
        }
        if ($buffer !== []) {
            $importer->upsertAuthorChunk($buffer);
        }

        return $processed;
    }

    protected function importWorks(OpenLibraryDumpImportService $importer, string $file, ?int $limit, int $chunk): int
    {
        $buffer = [];
        $processed = 0;
        foreach ($importer->eachParsedRowOfType($file, OpenLibraryDumpImportService::TYPE_WORK) as $parsed) {
            $item = $importer->buildWorkBatchItem($parsed);
            if ($item !== null) {
                $buffer[] = $item;
            }
            $processed++;
            if (count($buffer) >= $chunk) {
                $importer->flushWorksBatch($buffer);
                $buffer = [];
            }
            if ($limit !== null && $processed >= $limit) {
                break;
            }
        }
        if ($buffer !== []) {
            $importer->flushWorksBatch($buffer);
        }

        return $processed;
    }

    protected function importEditions(OpenLibraryDumpImportService $importer, string $file, ?int $limit, int $chunk): int
    {
        $buffer = [];
        $processed = 0;
        foreach ($importer->eachParsedRowOfType($file, OpenLibraryDumpImportService::TYPE_EDITION) as $parsed) {
            $item = $importer->buildEditionBatchItem($parsed);
            if ($item !== null) {
                $buffer[] = $item;
            }
            $processed++;
            if (count($buffer) >= $chunk) {
                $importer->flushEditionsBatch($buffer);
                $buffer = [];
            }
            if ($limit !== null && $processed >= $limit) {
                break;
            }
        }
        if ($buffer !== []) {
            $importer->flushEditionsBatch($buffer);
        }

        return $processed;
    }
}
