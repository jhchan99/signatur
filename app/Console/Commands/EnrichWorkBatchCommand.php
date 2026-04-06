<?php

namespace App\Console\Commands;

use App\Jobs\EnrichWorkJob;
use App\Models\Work;
use App\Services\Books\WorkEnrichmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonException;

#[Signature('works:enrich-batch {file : Path to a JSON batch file} {--sync : Run in the current process} {--delay=30 : Seconds between enrichments or queued jobs}')]
#[Description('Queue or run Open Library enrichment for a batch of works from JSON')]
class EnrichWorkBatchCommand extends Command
{
    public function handle(WorkEnrichmentService $enrichment): int
    {
        $delay = $this->parseDelaySeconds();
        if ($delay === null) {
            return self::FAILURE;
        }

        $entries = $this->readEntries((string) $this->argument('file'));
        if ($entries === null) {
            return self::FAILURE;
        }

        $runSync = (bool) $this->option('sync');
        $summary = [
            'total' => count($entries),
            'resolved' => 0,
            'queued' => 0,
            'enriched' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $queuedCount = 0;

        foreach ($entries as $index => $entry) {
            $entryNumber = $index + 1;
            $work = $this->resolveWork($entry);

            if (! $work instanceof Work) {
                $summary['failed']++;

                $this->components->warn(
                    sprintf(
                        'Skipped entry #%d%s (work could not be resolved).',
                        $entryNumber,
                        $this->entryLabel($entry),
                    ),
                );

                continue;
            }

            $summary['resolved']++;

            if (! $runSync) {
                $jobDelay = $queuedCount * $delay;

                EnrichWorkJob::dispatch((int) $work->getKey())->delay($jobDelay);

                $this->components->info(
                    sprintf(
                        'Queued work #%d (%s) with %d second delay.',
                        $work->getKey(),
                        $work->title,
                        $jobDelay,
                    ),
                );

                $summary['queued']++;
                $queuedCount++;

                continue;
            }

            $result = $enrichment->enrichWorkById((int) $work->getKey());
            $status = $result['status'] ?? 'unknown';
            $reason = $result['reason'] ?? null;

            if ($status === 'enriched') {
                $summary['enriched']++;
                $this->components->info("Enriched work #{$work->getKey()} ({$work->title}).");
            } elseif ($status === 'missing') {
                $summary['failed']++;
                $this->components->error("Work #{$work->getKey()} ({$work->title}) was not found.");
            } else {
                $summary['skipped']++;

                $reasonText = is_string($reason) ? " ({$reason})" : '';
                $this->components->warn("Skipped work #{$work->getKey()} ({$work->title}){$reasonText}.");
            }

            if ($delay > 0 && $index < count($entries) - 1) {
                sleep($delay);
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['total', $summary['total']],
                ['resolved', $summary['resolved']],
                ['queued', $summary['queued']],
                ['enriched', $summary['enriched']],
                ['skipped', $summary['skipped']],
                ['failed', $summary['failed']],
            ],
        );

        return self::SUCCESS;
    }

    protected function parseDelaySeconds(): ?int
    {
        $delay = filter_var(
            $this->option('delay'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]],
        );

        if ($delay === false) {
            $this->components->error('Delay must be a non-negative integer.');

            return null;
        }

        return $delay;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    protected function readEntries(string $path): ?array
    {
        $resolvedPath = $this->resolveBatchFilePath($path);
        if ($resolvedPath === null) {
            $this->components->error("Batch file [{$path}] was not found.");

            return null;
        }

        $contents = file_get_contents($resolvedPath);
        if ($contents === false) {
            $this->components->error("Batch file [{$resolvedPath}] could not be read.");

            return null;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->components->error("Batch file [{$resolvedPath}] does not contain valid JSON.");

            return null;
        }

        if (! is_array($decoded)) {
            $this->components->error("Batch file [{$resolvedPath}] must contain a top-level JSON array.");

            return null;
        }

        $entries = [];

        foreach ($decoded as $entry) {
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    protected function resolveBatchFilePath(string $path): ?string
    {
        $candidates = [$path];

        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $candidates[] = base_path($path);
            $candidates[] = storage_path($path);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function resolveWork(array $entry): ?Work
    {
        $workId = $entry['id'] ?? null;

        if (is_int($workId) && $workId > 0) {
            return Work::query()->find($workId);
        }

        if (is_string($workId) && ctype_digit($workId) && (int) $workId > 0) {
            return Work::query()->find((int) $workId);
        }

        $title = trim((string) ($entry['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        return Work::query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function entryLabel(array $entry): string
    {
        $title = trim((string) ($entry['title'] ?? ''));

        if ($title === '') {
            return '';
        }

        return " [{$title}]";
    }
}
