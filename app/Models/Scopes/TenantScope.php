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
        // The scope keys off the authenticated user, so console contexts that
        // legitimately span tenants (queue workers, scheduled commands) are
        // already unscoped simply by having no authenticated user.
        //
        // There used to be an explicit runningInConsole() bypass here. It was
        // redundant for those contexts and actively harmful in tests, where
        // actingAs() DOES authenticate: it silently disabled tenant isolation
        // across the whole suite, so no test could catch a cross-tenant leak.
        $user = Auth::user();

        if ($user && $user->current_team_id) {
            $builder->where($model->getTable().'.team_id', $user->current_team_id);
        }
    }
}
