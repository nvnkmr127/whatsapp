<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'last_login_at',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    /**
     * Determine if it's safe to unlink this identity (must have at least one other).
     */
    public function isSafeToUnlink(): bool
    {
        return $this->user->identities()->count() > 1;
    }

    /**
     * Get the user that owns the identity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
