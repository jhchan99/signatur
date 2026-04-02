<?php

namespace App\Jobs;

use App\Services\OpenLibrary\OpenLibraryWorkSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncWorkFromOpenLibraryJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $workKey,
    ) {}

    public function uniqueId(): string
    {
        return OpenLibraryWorkSyncService::normalizeWorkKey($this->workKey);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15, 45, 90];
    }

    public function handle(OpenLibraryWorkSyncService $sync): void
    {
        $sync->syncFromWorkKey($this->workKey);
    }
}
