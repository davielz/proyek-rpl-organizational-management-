<?php

use App\Http\Controllers\AdminAiConfigurationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AiDraftController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ManageEventController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserApprovalController;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Telegram Bot Webhook Route
Route::post('/telegram/webhook', function (Request $request, TelegramNotificationService $telegramService) {
    $update = $request->all();
    $telegramService->processTelegramUpdate($update);

    return response()->json(['ok' => true]);
})->name('telegram.webhook');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

// Event Catalog & Public Routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/chat', [ChatbotController::class, 'send'])->middleware('throttle:30,1')->name('dashboard.chat');
    Route::get('/dashboard/chat/history', [ChatbotController::class, 'history'])->name('dashboard.chat.history');
    Route::delete('/dashboard/chat', [ChatbotController::class, 'clear'])->name('dashboard.chat.clear');
    Route::post('/dashboard/chat/clear', [ChatbotController::class, 'clear'])->name('dashboard.chat.clear.post');

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

        // Account Approvals (Admin & Pengurus)
        Route::get('/approvals', [UserApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{user}/approve', [UserApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{user}/reject', [UserApprovalController::class, 'reject'])->name('approvals.reject');
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
        Route::post('/documents/generate-ai-draft', [AiDraftController::class, 'generate'])->middleware('throttle:10,1')->name('documents.generate-ai-draft');

        // Feedback & Sentiment Analysis Analytics (Admin & Pengurus)
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    });

    // Feedback Submission Route (All Authenticated Members)
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Polling Routes (All Authenticated Members can view list, detail, and vote)
    Route::get('/polls', [PollController::class, 'index'])->name('polls.index');
    Route::get('/polls/create', [PollController::class, 'create'])->name('polls.create');
    Route::post('/polls', [PollController::class, 'store'])->name('polls.store');
    Route::get('/polls/{poll}', [PollController::class, 'show'])->name('polls.show');
    Route::get('/polls/{poll}/edit', [PollController::class, 'edit'])->name('polls.edit');
    Route::put('/polls/{poll}', [PollController::class, 'update'])->name('polls.update');
    Route::delete('/polls/{poll}', [PollController::class, 'destroy'])->name('polls.destroy');
    Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])->middleware('throttle:15,1')->name('polls.vote');
    Route::post('/polls/{poll}/close', [PollController::class, 'close'])->name('polls.close');

    // Discussion Room Routes (All Authenticated Members)
    Route::get('/discussions', [DiscussionController::class, 'index'])->name('discussions.index');
    Route::get('/discussions/create', [DiscussionController::class, 'create'])->name('discussions.create');
    Route::post('/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
    Route::get('/discussions/{discussion}', [DiscussionController::class, 'show'])->name('discussions.show');
    Route::get('/discussions/{discussion}/edit', [DiscussionController::class, 'edit'])->name('discussions.edit');
    Route::put('/discussions/{discussion}', [DiscussionController::class, 'update'])->name('discussions.update');
    Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy'])->name('discussions.destroy');
    Route::post('/discussions/{discussion}/comments', [DiscussionController::class, 'storeComment'])->name('discussions.comments.store');
    Route::put('/discussion-comments/{comment}', [DiscussionController::class, 'updateComment'])->name('discussion-comments.update');
    Route::delete('/discussion-comments/{comment}', [DiscussionController::class, 'destroyComment'])->name('discussion-comments.destroy');

    // Admin Only Routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
        Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');

        // AI Assistant Configurations
        Route::get('/ai-configurations', [AdminAiConfigurationController::class, 'index'])->name('ai.index');
        Route::post('/ai-configurations', [AdminAiConfigurationController::class, 'store'])->name('ai.store');
        Route::put('/ai-configurations/{config}', [AdminAiConfigurationController::class, 'update'])->name('ai.update');
        Route::post('/ai-configurations/{config}/activate', [AdminAiConfigurationController::class, 'activate'])->name('ai.activate');
        Route::delete('/ai-configurations/{config}', [AdminAiConfigurationController::class, 'destroy'])->name('ai.destroy');
        Route::post('/ai-configurations/{config}/test', [AdminAiConfigurationController::class, 'test'])->name('ai.test');
    });
});
