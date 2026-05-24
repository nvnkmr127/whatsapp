<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ContactTagPivot extends Pivot
{
    protected $table = 'contact_tag_pivot';

    protected static function booted()
    {
        static::created(function ($pivot) {
            \App\Jobs\ProcessContactTagTriggerJob::dispatch((int)$pivot->contact_id, (int)$pivot->tag_id);
        });
    }
}
