<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AiDraftController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ManageEventController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Event Catalog & Public Routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/ui-showcase', function () {
        return view('ui-showcase');
    })->name('ui-showcase');

    // Event Registration Actions
    Route::post('/events/{event:slug}/register', [EventController::class, 'register'])->name('events.register');
    Route::post('/events/{event:slug}/cancel', [EventController::class, 'cancelRegistration'])->name('events.cancel-registration');

    // Admin & Pengurus Event Management Routes
    Route::middleware('role:admin,pengurus')->prefix('manage')->name('manage.')->group(function () {
        Route::get('/events', [ManageEventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [ManageEventController::class, 'create'])->name('events.create');
        Route::post('/events', [ManageEventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [ManageEventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [ManageEventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [ManageEventController::class, 'destroy'])->name('events.destroy');
        Route::put('/events/{event}/status', [ManageEventController::class, 'updateStatus'])->name('events.update-status');
    });

    // Proposal & LPJ Document Routes (Admin & Pengurus)
    Route::middleware('role:admin,pengurus')->group(function () {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/documents/{document}/submit', [DocumentController::class, 'submit'])->name('documents.submit');
        Route::post('/documents/{document}/review', [DocumentController::class, 'review'])->name('documents.review');
        Route::get('/documents/{document}/pdf', [DocumentController::class, 'exportPdf'])->name('documents.pdf');
        Route::post('/documents/generate-ai-draft', [AiDraftController::class, 'generate'])->name('documents.generate-ai-draft');
    });

    // Admin Only Routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
    });
});
