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

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    $resources = [
        'contacts' => ContactController::class,
        'statuses' => StatusController::class,
        'sources'  => SourceController::class,
    ];

    foreach ($resources as $resource => $controller) {
        Route::middleware("api.token:{$resource}.create")->post("/{$resource}", [$controller, 'store']);
        Route::middleware("api.token:{$resource}.read")->get("/{$resource}", [$controller, 'index']);
        Route::middleware("api.token:{$resource}.update")->put("/{$resource}/{id}", [$controller, 'update']);
        Route::middleware("api.token:{$resource}.delete")->delete("/{$resource}/{id}", [$controller, 'destroy']);
    }
});
