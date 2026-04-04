<?php

namespace App\Jobs;

use App\Services\Books\WorkEnrichmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnrichWorkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $workId,
    ) {}

    public function handle(WorkEnrichmentService $enrichment): void
    {
        $enrichment->enrichWorkById($this->workId);
    }
}
