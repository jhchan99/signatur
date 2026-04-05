<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/*
|--------------------------------------------------------------------------
| Livewire update URL (POST-only)
|--------------------------------------------------------------------------
|
| Browsers, bots, or speculative prefetch can issue GET to this path. Without
| a GET route, Laravel raises MethodNotAllowedHttpException. Reject cleanly.
|
*/
Route::match(['get', 'head'], EndpointResolver::updatePath(), function (): void {
    abort(404);
});

Route::get('/', HomeController::class)->name('home');
Route::get('/search', SearchController::class)->name('search.index');
Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/authors/{author}', [AuthorController::class, 'show'])->name('authors.show');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{work}', [BookController::class, 'show'])->name('books.show');
Route::get('/collections', CollectionsController::class)->name('collections.index');
Route::view('/login', 'pages.auth.login')->name('login');
Route::redirect('/dashboard', '/')->name('dashboard');

require __DIR__.'/settings.php';
