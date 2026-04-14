<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\OverviewController;

Route::get('/', function () {
    return redirect()->route('overview'); 
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/kanban', [BoardController::class, 'index'])->name('kanban.index');
    Route::post('/kanban', [BoardController::class, 'store'])->name('kanban.store');
    Route::post('/boards/{board}/columns', [ColumnController::class, 'store'])->name('columns.store');
    Route::post('/columns/{column}/cards', [CardController::class, 'store'])->name('cards.store');
    Route::put('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('/boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');
    Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::patch('/boards/{board}/favorite', [App\Http\Controllers\BoardController::class, 'toggleFavorite'])->name('boards.favorite');
});

require __DIR__.'/auth.php';
