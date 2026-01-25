<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'security_metadata' => 'json',
        ];
    }
    /**
     * Get all of the teams the user belongs to or owns.
     *
     * @return \Illuminate\Support\Collection
     */
    public function allTeams()
    {
        if ($this->is_super_admin) {
            return \App\Models\Team::all();
        }

        return $this->ownedTeams->merge($this->teams)->sortBy('name');
    }

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        if ($this->is_super_admin) {
            return true;
        }

        if (is_null($team)) {
            return false;
        }

        return $this->ownsTeam($team) || $this->teams->contains(function ($t) use ($team) {
            return $t->id === $team->id;
        });
    }

    /**
     * Get the role that the user has on the team.
     *
     * @param  mixed  $team
     * @return \Laravel\Jetstream\Role|null
     */
    public function teamRole($team)
    {
        if ($this->is_super_admin) {
            return new \Laravel\Jetstream\Role(
                'admin',
                'Administrator',
                ['*']
            );
        }

        if ($this->ownsTeam($team)) {
            return new \Laravel\Jetstream\OwnerRole;
        }

        if (!$this->belongsToTeam($team)) {
            return;
        }

        $role = $team->users->where('id', $this->id)->first()->membership->role;

        return \Laravel\Jetstream\Jetstream::findRole($role);
    }

    /**
     * Get the identities for the user.
     */
    public function identities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    /**
     * Determine if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Revoke all of the user's sessions except the current one.
     */
    public function revokeOtherSessions()
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('id', '<>', \Illuminate\Support\Facades\Session::getId())
            ->delete();
    }

    /**
     * Revoke all of the user's API tokens.
     */
    public function revokeAllTokens()
    {
        $this->tokens()->delete();
    }

    /**
     * Determine if the user has a specific plan feature in their current team.
     */
    public function hasPlanFeature(string $feature): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->currentTeam) {
            return false;
        }

        return $this->currentTeam->hasFeature($feature);
    }
}
