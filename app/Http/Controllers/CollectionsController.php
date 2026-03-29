<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class CollectionsController extends Controller
{
    /**
     * Show the placeholder curated collections page.
     */
    public function __invoke(): View
    {
        return view('collections.index', [
            'title' => __('Collections'),
        ]);
    }
}
