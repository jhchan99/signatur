<?php

namespace App\Http\Controllers;

use App\Services\Books\HomepageFeaturedBooksService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageFeaturedBooksService $featuredBooks): View
    {
        $payload = $featuredBooks->featuredPayload();

        return view('welcome', [
            'title' => __('Track what you read'),
            'heroBook' => $payload['heroBook'],
            'featuredCovers' => $payload['featuredCovers'],
        ]);
    }
}
