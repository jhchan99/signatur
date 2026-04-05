<?php

namespace App\Services\OpenLibrary;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class OpenLibraryService
{
    protected const RATE_LIMITER_KEY = 'openlibrary:http';

    protected int $maxRequestsPerSecond;

    protected string $baseUrl;

    protected int $timeout;

    protected int $connectTimeout;

    protected string $userAgent;

    public function __construct()
    {
        $this->maxRequestsPerSecond = (int) config('services.openlibrary.max_requests_per_second', 3);
        $configured = config('services.openlibrary.base_url');
        $this->baseUrl = is_string($configured) && $configured !== ''
            ? $configured
            : 'https://openlibrary.org';
        $this->timeout = (int) config('services.openlibrary.timeout');
        $this->connectTimeout = (int) config('services.openlibrary.connect_timeout');
        $configuredUserAgent = config('services.openlibrary.user_agent');
        $this->userAgent = is_string($configuredUserAgent) && trim($configuredUserAgent) !== ''
            ? trim($configuredUserAgent)
            : 'Signatr (jhchan99@gmail.com)';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchDocuments(string $q, int $limit = 25): Collection
    {
        $payload = $this->get('search.json', [
            'q' => $q,
            'limit' => $limit,
        ], degradeOnTransportFailure: true);

        return $this->normalizeSearchDocs($payload);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchAuthorsByName(string $name, int $limit = 10): Collection
    {
        $payload = $this->get('search/authors.json', [
            'q' => $name,
            'limit' => $limit,
        ], degradeOnTransportFailure: true);

        return $this->normalizeSearchDocs($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWork(string $workKey): array
    {
        $path = trim($workKey, '/').'.json';

        return $this->get($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAuthor(string $authorKey): array
    {
        $path = trim($authorKey, '/').'.json';

        return $this->get($path);
    }

    /**
     * @param  array<string, mixed|array<int|string, mixed>>  $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query = [], bool $degradeOnTransportFailure = false): array
    {
        $this->waitForOutgoingRequestSlot();
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

        try {
            /** @var Response $response */
            $response = $this->pendingRequest()
                ->get($url, $query);
        } catch (ConnectionException $e) {
            if ($degradeOnTransportFailure) {
                return [];
            }

            throw $e;
        }

        if ($response->failed()) {
            return [];
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    protected function pendingRequest(): PendingRequest
    {
        $pending = Http::timeout($this->timeout)
            ->retry(3, 250, throw: false)
            ->acceptJson()
            ->withUserAgent($this->userAgent);

        if ($this->connectTimeout > 0) {
            $pending->connectTimeout($this->connectTimeout);
        }

        return $pending;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeSearchDocs(array $payload): Collection
    {
        $docs = $payload['docs'] ?? [];
        if (! is_array($docs)) {
            return collect();
        }

        $out = [];
        foreach ($docs as $doc) {
            if (is_array($doc)) {
                /** @var array<string, mixed> $doc */
                $out[] = $doc;
            }
        }

        return collect($out);
    }

    protected function waitForOutgoingRequestSlot(): void
    {
        if ($this->maxRequestsPerSecond <= 0) {
            return;
        }

        $key = self::RATE_LIMITER_KEY;

        while (RateLimiter::tooManyAttempts($key, $this->maxRequestsPerSecond)) {
            $seconds = RateLimiter::availableIn($key);
            if ($seconds > 0) {
                sleep($seconds);
                continue;
            }
            usleep(100000);
        }
        RateLimiter::hit($key, 1);
    }
}
