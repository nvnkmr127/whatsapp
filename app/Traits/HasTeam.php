<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait HasTeam
{
    /**
     * Boot the trait to add the global scope and set team_id on creation.
     */
    protected static function bootHasTeam()
    {
        static::addGlobalScope(new TenantScope);

        // Automatically set team_id on creation
        static::creating(function ($model) {
            if (!$model->team_id && Auth::check()) {
                $model->team_id = Auth::user()->current_team_id;
            }
        });
    }

    /**
     * Get the team associated with the model.
     */
    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}
