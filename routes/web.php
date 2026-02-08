<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');

    // Contact routes
    Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('contacts/create/{company_id}', [ContactController::class, 'create'])->name('contacts.create');
    Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('contacts/{id}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('contacts/{id}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('contacts/{id}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('contacts/{id}/toggle-contacted', [ContactController::class, 'toggleContacted'])->name('contacts.toggle-contacted');

    // Campaign routes
    Route::resource('campaigns', App\Http\Controllers\CampaignController::class);
    Route::post('campaigns/{campaign}/add-contacts', [App\Http\Controllers\CampaignController::class, 'addContacts'])->name('campaigns.add-contacts');
    Route::post('campaigns/{campaign}/remove-contacts', [App\Http\Controllers\CampaignController::class, 'removeContacts'])->name('campaigns.remove-contacts');
    Route::post('campaigns/{campaign}/add-companies', [App\Http\Controllers\CampaignController::class, 'addCompanies'])->name('campaigns.add-companies');
    Route::post('campaigns/{campaign}/remove-companies', [App\Http\Controllers\CampaignController::class, 'removeCompanies'])->name('campaigns.remove-companies');
    Route::post('campaigns/{campaign}/schedule', [App\Http\Controllers\CampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/send', [App\Http\Controllers\CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('campaigns/{campaign}/pause', [App\Http\Controllers\CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/resume', [App\Http\Controllers\CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('campaigns/{campaign}/stop', [App\Http\Controllers\CampaignController::class, 'stop'])->name('campaigns.stop');
    Route::put('campaigns/{campaign}/contacts/{campaignContact}/status', [App\Http\Controllers\CampaignController::class, 'updateContactStatus'])->name('campaigns.contacts.update-status');

    // Campaign segment routes
    Route::post('campaigns/{campaign}/segments', [App\Http\Controllers\CampaignSegmentController::class, 'store'])->name('campaigns.segments.store');
    Route::put('campaigns/{campaign}/segments/{segment}', [App\Http\Controllers\CampaignSegmentController::class, 'update'])->name('campaigns.segments.update');
    Route::delete('campaigns/{campaign}/segments', [App\Http\Controllers\CampaignSegmentController::class, 'destroy'])->name('campaigns.segments.destroy');
    Route::post('campaigns/{campaign}/segments/{segment}/send', [App\Http\Controllers\CampaignSegmentController::class, 'send'])->name('campaigns.segments.send');

    // Sequence routes
    Route::resource('sequences', App\Http\Controllers\SequenceController::class);
    Route::post('sequences/{sequence}/activate', [App\Http\Controllers\SequenceController::class, 'activate'])->name('sequences.activate');
    Route::post('sequences/{sequence}/pause', [App\Http\Controllers\SequenceController::class, 'pause'])->name('sequences.pause');
    Route::post('sequences/{sequence}/contacts', [App\Http\Controllers\SequenceController::class, 'addContacts'])->name('sequences.add-contacts');
    Route::delete('sequences/{sequence}/contacts/{contact}', [App\Http\Controllers\SequenceController::class, 'removeContact'])->name('sequences.remove-contact');
});

// Email webhook for handling provider events (e.g. Resend.com)
Route::withoutMiddleware(['auth', 'web'])
    ->post('email/webhook', [App\Http\Controllers\EmailTrackingController::class, 'handleWebhook'])
    ->name('email.webhook');

// Unsubscribe route - should be accessible without authentication
Route::get('email/unsubscribe/{campaign}/{contact}', [App\Http\Controllers\EmailTrackingController::class, 'unsubscribe'])
    ->name('email.unsubscribe');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
