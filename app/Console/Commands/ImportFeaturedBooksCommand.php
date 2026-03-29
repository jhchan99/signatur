<?php

namespace App\Console\Commands;

use App\Jobs\ImportFeaturedBooksJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('books:import-featured {--sync : Run the import in the current process}')]
#[Description('Queue or run a featured-books import from Open Library')]
class ImportFeaturedBooksCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('sync')) {
            ImportFeaturedBooksJob::dispatchSync();
            $this->components->info('Featured books import completed.');
        } else {
            ImportFeaturedBooksJob::dispatch();
            $this->components->info('Featured books import queued.');
        }

        return self::SUCCESS;
    }
}
