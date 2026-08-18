<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\KaizenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/kaizens/create', [KaizenController::class, 'create'])->name('kaizens.create');
    Route::get('/kaizens/{kaizen}', [KaizenController::class, 'show'])->name('kaizens.show');
    Route::post('/kaizens', [KaizenController::class, 'store'])->name('kaizens.store');
    Route::patch('/kaizens/{kaizen}', [KaizenController::class, 'update'])->name('kaizens.update');
    Route::post('/kaizens/{kaizen}/submit', [KaizenController::class, 'submit'])->name('kaizens.submit');
});
