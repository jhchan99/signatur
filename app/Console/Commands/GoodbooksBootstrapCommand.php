<?php

namespace App\Console\Commands;

use App\Services\Books\GoodbooksBootstrapService;
use Illuminate\Console\Command;

class GoodbooksBootstrapCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'goodbooks:bootstrap
        {--force : Truncate catalog tables and replace with Goodbooks data}
        {--path= : Path to unpacked goodbooks-10k-master directory (defaults to config)}';

    /**
     * @var string
     */
    protected $description = 'Import curated catalog rows from the Goodbooks 10k dataset';

    public function handle(GoodbooksBootstrapService $service): int
    {
        $pathRaw = $this->option('path');
        $dataDir = is_string($pathRaw) && $pathRaw !== ''
            ? $pathRaw
            : (string) config('goodbooks.data_dir');

        $force = (bool) $this->option('force');

        if (! $force && ! $this->output->isQuiet()) {
            $this->warn('Without --force, import only runs when works is empty. Use --force to replace the catalog.');
        }

        try {
            $count = $service->bootstrap(
                $dataDir,
                $force,
                function (string $message): void {
                    $this->line($message);
                },
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported {$count} work(s) from Goodbooks.");

        return self::SUCCESS;
    }
}
