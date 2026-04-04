<?php

namespace App\Console\Commands;

use App\Jobs\EnrichWorkJob;
use App\Services\Books\WorkEnrichmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('works:enrich-work {work : Local work id} {--sync : Run in the current process}')]
#[Description('Queue or run Open Library enrichment for a single work')]
class EnrichWorkCommand extends Command
{
    public function handle(WorkEnrichmentService $enrichment): int
    {
        $workId = (int) $this->argument('work');
        if ($workId <= 0) {
            $this->components->error('Work id must be a positive integer.');

            return self::FAILURE;
        }

        if (! $this->option('sync')) {
            EnrichWorkJob::dispatch($workId);
            $this->components->info("Queued enrichment for work #{$workId}.");

            return self::SUCCESS;
        }

        $result = $enrichment->enrichWorkById($workId);
        $status = $result['status'] ?? 'unknown';
        $reason = $result['reason'] ?? null;

        if ($status === 'enriched') {
            $this->components->info("Enriched work #{$workId}.");

            return self::SUCCESS;
        }

        if ($status === 'missing') {
            $this->components->error("Work #{$workId} was not found.");

            return self::FAILURE;
        }

        $reasonText = is_string($reason) ? " ({$reason})" : '';
        $this->components->warn("Skipped work #{$workId}{$reasonText}.");

        return self::SUCCESS;
    }
}
