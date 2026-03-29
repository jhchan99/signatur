<?php

namespace App\Services\OpenLibrary;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class OpenLibraryService
{
    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $configured = config('services.openlibrary.base_url');
        $this->baseUrl = is_string($configured) && $configured !== ''
            ? $configured
            : 'https://openlibrary.org';
        $this->timeout = (int) config('services.openlibrary.timeout');
    }

    /**
     * @return Collection<int|string, mixed>
     */
    public function search(string $query): Collection
    {
        $payload = $this->get('search.json', [
            'q' => $query,
            'limit' => 20,
        ]);

        return collect($payload);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchDocuments(string $query): Collection
    {
        $payload = $this->get('search.json', [
            'q' => $query,
            'limit' => 20,
        ]);

        $docs = $payload['docs'] ?? [];

        if (! is_array($docs)) {
            return collect();
        }

        /** @var array<int, array<string, mixed>> $docs */
        return collect($docs);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function searchDocumentsByTitleAndAuthor(string $title, string $author): Collection
    {
        $payload = $this->get('search.json', [
            'title' => $title,
            'author' => $author,
            'limit' => 10,
        ]);

        $docs = $payload['docs'] ?? [];

        if (! is_array($docs)) {
            return collect();
        }

        /** @var array<int, array<string, mixed>> $docs */
        return collect($docs);
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
    protected function get(string $path, array $query = []): array
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

        /** @var Response $response */
        $response = Http::timeout($this->timeout)
            ->retry(3, 250, throw: false)
            ->acceptJson()
            ->get($url, $query);

        if ($response->failed()) {
            return [];
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }
}
