<?php

use Illuminate\Support\Facades\Route;
use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Http\Request;

Route::view('/', 'welcome')->name('home');

Route::get('/search', function (Request $request, OpenLibraryService $openLibrary) {
    $results = $openLibrary->search($request->input('query'));
    return response()->json($results);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/settings.php';
