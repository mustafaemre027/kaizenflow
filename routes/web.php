<?php

use App\Http\Controllers\KaizenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::post('/kaizens', [KaizenController::class, 'store'])->name('kaizens.store');
    Route::patch('/kaizens/{kaizen}', [KaizenController::class, 'update'])->name('kaizens.update');
    Route::post('/kaizens/{kaizen}/submit', [KaizenController::class, 'submit'])->name('kaizens.submit');
});
