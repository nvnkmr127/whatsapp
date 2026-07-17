<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OAuthClient extends Model
{
    use HasUuids;

    protected $table = 'oauth_clients';

    protected $fillable = ['name', 'redirect_uris'];

    protected $casts = ['redirect_uris' => 'array'];
}
