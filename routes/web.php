<?php

use App\Http\Controllers\AiDocumentQAController;
use App\Http\Controllers\TicketChatController;
use App\Http\Controllers\TicketDraftReplyStreamController;
use App\Http\Controllers\TicketTriagerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::livewire('tickets', 'pages::tickets.index')->name('tickets.index');
    Route::livewire('tickets/{ticket}', 'pages::tickets.show')->name('tickets.show');

    Route::post('tickets/{ticket}/ai/triage',TicketTriagerController::class)
        ->name('tickets.ai.triager');

    Route::post('tickets/{ticket}/ai/chat', TicketChatController::class)
        ->name('tickets.ai.chat');

    Route::post('tickets/{ticket}/ai/draft-reply/stream', TicketDraftReplyStreamController::class)
        ->name('tickets.ai.draft-reply.stream');

    Route::get('documents/search', \App\Http\Controllers\AiKnowledgeSearchController::class)
        ->name('ai.knowledge-search');

    Route::get('ai/document-qa', [AiDocumentQAController::class, 'index'])
        ->name('ai.document-qa');
    Route::post('ai/document-qa/upload', [AiDocumentQaController::class, 'store'])
        ->name('ai.document-qa.upload');
    Route::post('ai/document-qa/ask', [AiDocumentQaController::class, 'ask'])
        ->name('ai.document-qa.ask');
    Route::delete('ai/document-qa/{uploadedDocument}', [AiDocumentQaController::class, 'destroy'])
        ->name('ai.document-qa.delete');
});


require __DIR__.'/settings.php';
