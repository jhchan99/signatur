<?php

namespace App\Jobs;

use App\Services\Books\FeaturedBooksImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportFeaturedBooksJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(FeaturedBooksImporter $importer): void
    {
        $importer->import();
    }
}
