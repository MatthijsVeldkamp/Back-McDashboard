<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ServerController;
use Inertia\Inertia;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/hello', [ApiController::class, 'hello']);
Route::get('/name', [ApiController::class, 'getName']);
require __DIR__.'/auth.php';
Route::get('/test', [TestController::class, 'test']);
Route::get('/start', [BackupController::class, 'start']);
Route::get('/stop', [BackupController::class, 'stop']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
});