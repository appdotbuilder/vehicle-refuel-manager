<?php

use App\Http\Controllers\RefuelingRequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/health-check', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
})->name('health-check');

// Home page - shows refueling requests based on user role or welcome page for guests
Route::get('/', [RefuelingRequestController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    // Refueling request routes
    Route::resource('refueling-requests', RefuelingRequestController::class)->except(['index', 'create', 'edit']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
