<?php

namespace App\Providers;

use App\MergeFields\ContactMergeFields;
use App\MergeFields\OtherMergeFields;
use App\MergeFields\UserMergeFields;
use App\Services\MergeFields;
use Illuminate\Support\ServiceProvider;

class MergeFieldsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MergeFields::class, function () {
            return new MergeFields;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(MergeFields $mergeFields): void
    {
        $mergeFields->register(OtherMergeFields::class);
        $mergeFields->register(ContactMergeFields::class);
        $mergeFields->register(UserMergeFields::class);
    }
}
