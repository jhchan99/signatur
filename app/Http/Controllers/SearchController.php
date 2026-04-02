<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Services\Books\GlobalCatalogSearchService;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __invoke(GlobalSearchRequest $request, GlobalCatalogSearchService $catalogSearch): View
    {
        $validated = $request->validatedQuery();
        $result = $catalogSearch->search($validated['q']);

        return view('search.index', [
            'title' => __('Search'),
            'result' => $result,
            'query' => $validated['q'],
        ]);
    }
}
