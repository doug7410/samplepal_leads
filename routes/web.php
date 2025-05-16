<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');

    // Contact routes
    Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('contacts/create/{company_id}', [ContactController::class, 'create'])->name('contacts.create');
    Route::post('contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('contacts/{id}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('contacts/{id}', [ContactController::class, 'update'])->name('contacts.update');
    Route::post('contacts/{id}/toggle-contacted', [ContactController::class, 'toggleContacted'])->name('contacts.toggle-contacted');
    
    // Campaign routes
    Route::resource('campaigns', App\Http\Controllers\CampaignController::class);
    Route::post('campaigns/{campaign}/add-contacts', [App\Http\Controllers\CampaignController::class, 'addContacts'])->name('campaigns.add-contacts');
    Route::post('campaigns/{campaign}/remove-contacts', [App\Http\Controllers\CampaignController::class, 'removeContacts'])->name('campaigns.remove-contacts');
    Route::post('campaigns/{campaign}/schedule', [App\Http\Controllers\CampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/send', [App\Http\Controllers\CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('campaigns/{campaign}/pause', [App\Http\Controllers\CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/resume', [App\Http\Controllers\CampaignController::class, 'resume'])->name('campaigns.resume');
    
    // Email tracking routes
    Route::get('email/track/open/{campaign}/{contact}', [App\Http\Controllers\EmailTrackingController::class, 'trackOpen'])->name('email.track.open');
    Route::get('email/track/click/{campaign}/{contact}', [App\Http\Controllers\EmailTrackingController::class, 'trackClick'])->name('email.track.click');
    Route::post('email/track/responded/{campaign}/{contact}', [App\Http\Controllers\EmailTrackingController::class, 'markAsResponded'])->name('email.track.responded');
    Route::post('email/webhook/ses', [App\Http\Controllers\EmailTrackingController::class, 'handleSesWebhook'])->name('email.webhook.ses');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
