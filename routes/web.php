<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{work}', [BookController::class, 'show'])->name('books.show');
Route::get('/collections', CollectionsController::class)->name('collections.index');
Route::view('/login', 'pages.auth.login')->name('login');
Route::redirect('/dashboard', '/')->name('dashboard');

require __DIR__.'/settings.php';
