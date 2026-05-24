<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactTag extends Model
{
    use \App\Traits\HasTeam;

    protected $guarded = [];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_tag_pivot', 'tag_id', 'contact_id')
            ->using(ContactTagPivot::class);
    }
}
