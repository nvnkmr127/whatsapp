<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // NOTE: this bypass also disables tenant isolation in the entire test
        // suite (PHPUnit runs in console), so no test can currently catch a
        // cross-tenant leak. Removing it is safe for production — web requests
        // are already scoped, and console contexts have no authenticated user
        // so they stay unscoped either way — but it turns 4 existing tests red,
        // each of which needs individual triage first. See the notes on
        // SendMessageJob::58 and ApiIntegrationTest before attempting it.
        if (app()->runningInConsole()) {
            return;
        }

        $user = Auth::user();

        if ($user && $user->current_team_id) {
            $builder->where($model->getTable().'.team_id', $user->current_team_id);
        }
    }
}
