<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\KnowledgeArticleController;
use App\Http\Controllers\Admin\KnowledgeCategoryController;
use App\Http\Controllers\Admin\ShofolModuleController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // AI Support Chat Assistant routes
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/new', [ChatController::class, 'newConversation'])->name('chat.new');

    // Conversation History routes
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

    // Support Ticket routes (User Portal)
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::resource('knowledge-articles', KnowledgeArticleController::class);

    // Shofol Dynamic Module Management routes
    Route::patch('/modules/{module}/toggle', [ShofolModuleController::class, 'toggle'])->name('modules.toggle');
    Route::resource('modules', ShofolModuleController::class);

    // Dynamic Knowledge Category Management routes
    Route::patch('/categories/{category}/toggle', [KnowledgeCategoryController::class, 'toggle'])->name('categories.toggle');
    Route::resource('categories', KnowledgeCategoryController::class);

    // Dynamic FAQ Management routes
    Route::patch('/faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('faqs.toggle');
    Route::resource('faqs', FaqController::class);

    // Support Ticket routes (Admin Portal)
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}', [AdminTicketController::class, 'update'])->name('tickets.update');
});

require __DIR__.'/auth.php';