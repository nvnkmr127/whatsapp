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
        // Don't apply in console (unless specified) or for Super Admins in admin routes
        if (app()->runningInConsole()) {
            return;
        }

        $user = Auth::user();

        if ($user && $user->current_team_id) {
            $builder->where($model->getTable() . '.team_id', $user->current_team_id);
        }
    }
}
