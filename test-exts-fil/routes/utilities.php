<?php

//  Add your custom routes here

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // add routes for storage links
    Route::get('/storage-link', function () {
        Artisan::call('storage:link');

        return response()->json(['message' => 'Storage link created successfully.']);
    })->name('storage.link');

    // add rotes for clearing cache
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return response()->json(['message' => 'Cache cleared successfully.']);
    })->name('cache.clear');

});
