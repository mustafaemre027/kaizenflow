<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\KaizenApprovalActionController;
use App\Http\Controllers\KaizenAttachmentController;
use App\Http\Controllers\KaizenController;
use App\Http\Controllers\KaizenImplementationController;
use App\Http\Controllers\Settings\ApprovalConfigurationController;
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
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

    // Implementation Execution Routes
    Route::post('/kaizens/{kaizen}/implementation/assign', [KaizenImplementationController::class, 'assign'])->name('kaizens.implementation.assign');
    Route::post('/kaizens/{kaizen}/implementation/start', [KaizenImplementationController::class, 'start'])->name('kaizens.implementation.start');
    Route::post('/kaizens/{kaizen}/implementation/complete', [KaizenImplementationController::class, 'complete'])->name('kaizens.implementation.complete');

    Route::get('/kaizens/create', [KaizenController::class, 'create'])->name('kaizens.create');
    Route::get('/kaizens/{kaizen}/edit', [KaizenController::class, 'edit'])->name('kaizens.edit');
    Route::get('/kaizens/{kaizen}', [KaizenController::class, 'show'])->name('kaizens.show');
    Route::post('/kaizens', [KaizenController::class, 'store'])->name('kaizens.store');
    Route::patch('/kaizens/{kaizen}', [KaizenController::class, 'update'])->name('kaizens.update');
    Route::post('/kaizens/{kaizen}/submit', [KaizenController::class, 'submit'])->name('kaizens.submit');
    Route::post('/kaizens/{kaizen}/workflow/approve', [KaizenApprovalActionController::class, 'approve'])->name('kaizens.workflow.approve');
    Route::post('/kaizens/{kaizen}/workflow/request-revision', [KaizenApprovalActionController::class, 'requestRevision'])->name('kaizens.workflow.request-revision');
    Route::post('/kaizens/{kaizen}/workflow/reject', [KaizenApprovalActionController::class, 'reject'])->name('kaizens.workflow.reject');

    Route::get('/kaizens/{kaizen}/attachments/{attachment}', [KaizenAttachmentController::class, 'show'])->name('kaizens.attachments.show');
    Route::get('/kaizens/{kaizen}/attachments/{attachment}/download', [KaizenAttachmentController::class, 'download'])->name('kaizens.attachments.download');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/reference-data', [ReferenceDataController::class, 'index'])->name('reference-data.index');

        Route::get('/approval-configurations', [ApprovalConfigurationController::class, 'index'])->name('approval-configurations.index');
        Route::get('/approval-configurations/create', [ApprovalConfigurationController::class, 'create'])->name('approval-configurations.create');
        Route::get('/approval-configurations/{id}', [ApprovalConfigurationController::class, 'show'])->name('approval-configurations.show')->where('id', '[0-9]+');
        Route::get('/approval-configurations/{id}/edit', [ApprovalConfigurationController::class, 'edit'])->name('approval-configurations.edit')->where('id', '[0-9]+');
        Route::post('/approval-configurations', [ApprovalConfigurationController::class, 'store'])->name('approval-configurations.store');
        Route::patch('/approval-configurations/{id}', [ApprovalConfigurationController::class, 'update'])->name('approval-configurations.update');
        Route::post('/approval-configurations/{id}/publish', [ApprovalConfigurationController::class, 'publish'])->name('approval-configurations.publish');
        Route::post('/approval-configurations/{id}/default', [ApprovalConfigurationController::class, 'setDefault'])->name('approval-configurations.set-default');
        Route::post('/approval-configurations/{id}/deactivate', [ApprovalConfigurationController::class, 'deactivate'])->name('approval-configurations.deactivate');

        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/status', [CategoryController::class, 'toggleStatus'])->name('categories.status');

        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::patch('/departments/{department}/status', [DepartmentController::class, 'toggleStatus'])->name('departments.status');
    });
});
