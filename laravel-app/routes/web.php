<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/mushaf', [PublicController::class, 'mushaf'])->name('mushaf');
Route::get('/names/{nameId}', [PublicController::class, 'namePage'])->name('name.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/admin/review', [AdminController::class, 'review'])->name('admin.review');
        Route::post('/admin/narrative', [AdminController::class, 'addNarrative'])->name('admin.narrative');
    });
});
