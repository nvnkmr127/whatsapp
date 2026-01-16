<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['team_id', 'target_module', 'name', 'description', 'color', 'icon', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
