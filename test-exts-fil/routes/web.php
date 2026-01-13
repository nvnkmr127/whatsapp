<?php

/*
Project         :   WhatsApp Marketing & Automation Platform with Bots, Chats, Bulk Sender & AI
@package        :   Laravel
Laravel Version :   11.41.3
PHP Version     :   8.2.18
Created Date    :   14-01-2025
Copyright       :   Corbital Technologies LLP
Author          :   CORBITALTECH™
Author URL      :   https://codecanyon.net/user/corbitaltech
Support         :   contact@corbitaltech.dev
License         :   Licensed under Codecanyon Licence
*/

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DatabaseUpgrade;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsApp\WhatsAppWebhookController;
use Corbital\Installer\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

// Public Route (Login Page)
Route::get('/', [AuthenticatedSessionController::class, 'create']);
// Add the database upgrade route
Route::get('/database-upgrade', [DatabaseUpgrade::class, 'index'])->name('database.upgrade');
Route::post('/upgrade', [DatabaseUpgrade::class, 'upgrade'])->name('upgrade');

Route::get('/validate', [InstallController::class, 'validate'])->name('validate');
Route::post('/validate', [InstallController::class, 'validateLicense'])->name('validate.license');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

// WhatsApp Webhook Route (Supports GET & POST)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppWebhookController::class, '__invoke'])
    ->name('whatsapp.webhook');
// Authentication Routes
require __DIR__ . '/auth.php';
