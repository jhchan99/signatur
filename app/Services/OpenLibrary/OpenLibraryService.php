<?php

namespace App\Services\OpenLibrary;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/*
    Owns all OpenLibrary HTTP details - base URL, timeouts, query-params
    Expose small methods that match app needs (search, work by ID, ISBN lookup)
    Returns data 
*/

class OpenLibraryService
{

    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.openlibrary.base_url');
        $this->timeout = config('services.openlibrary.timeout');
    }

    public function search(string $query): Collection
    {
        $url = $this->buildSearchUrl($query);
        $response = Http::timeout($this->timeout)->get($url);
        return collect($response->json());
    }

    protected function buildSearchUrl(string $query): string
    {
        return $this->baseUrl . '/search.json?q=' . urlencode($query);
    }
}
