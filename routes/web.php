<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\HomeController;
use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/collections', CollectionsController::class)->name('collections.index');
Route::view('/login', 'pages.auth.login')->name('login');
Route::redirect('/dashboard', '/')->name('dashboard');

Route::get('/search', function (Request $request, OpenLibraryService $openLibrary) {
    $results = $openLibrary->search($request->input('query'));

    return response()->json($results);
});

require __DIR__.'/settings.php';
