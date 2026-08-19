<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\KaizenController;
use App\Http\Controllers\Settings\CategoryController;
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\ReferenceDataController;
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
    Route::get('/kaizens', [KaizenController::class, 'index'])->name('kaizens.index');
    Route::get('/kaizens/create', [KaizenController::class, 'create'])->name('kaizens.create');
    Route::get('/kaizens/{kaizen}/edit', [KaizenController::class, 'edit'])->name('kaizens.edit');
    Route::get('/kaizens/{kaizen}', [KaizenController::class, 'show'])->name('kaizens.show');
    Route::post('/kaizens', [KaizenController::class, 'store'])->name('kaizens.store');
    Route::patch('/kaizens/{kaizen}', [KaizenController::class, 'update'])->name('kaizens.update');
    Route::post('/kaizens/{kaizen}/submit', [KaizenController::class, 'submit'])->name('kaizens.submit');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/reference-data', [ReferenceDataController::class, 'index'])->name('reference-data.index');

        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/status', [CategoryController::class, 'toggleStatus'])->name('categories.status');

        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::patch('/departments/{department}/status', [DepartmentController::class, 'toggleStatus'])->name('departments.status');
    });
});
