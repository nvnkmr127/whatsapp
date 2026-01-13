<?php

namespace App\Models;

use App\Enums\ContactType;
use App\Traits\HasWebhooks;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                             $id
 * @property string                          $firstname
 * @property string                          $lastname
 * @property string|null                     $company
 * @property string                          $type
 * @property string|null                     $description
 * @property int|null                        $country_id
 * @property string|null                     $zip
 * @property string|null                     $city
 * @property string|null                     $state
 * @property string|null                     $address
 * @property int|null                        $assigned_id
 * @property int                             $status_id
 * @property int                             $source_id
 * @property string|null                     $email
 * @property string|null                     $website
 * @property string                          $phone
 * @property int|null                        $is_enabled
 * @property int                             $addedfrom
 * @property string|null                     $dateassigned
 * @property string|null                     $last_status_change
 * @property string|null                     $default_language
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \App\Models\Country|null $country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContactNote> $notes
 * @property-read int|null $notes_count
 * @property-read \App\Models\Source $source
 * @property-read \App\Models\Status $status
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 *
 * @property-read mixed $formatted_phone
 * @property-read mixed $full_name
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact byPhone($phone)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact byType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereAddedfrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereAssignedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereDateassigned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereLastStatusChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereZip($value)
 *
 * @mixin \Eloquent
 */
class Contact extends Model
{
    use HasWebhooks;

    protected $with = [
        'status:id,name,color',
        'source:id,name',
        'assignedTo:id,firstname,lastname,profile_image_url',
    ];

    protected $fillable = [
        'firstname',
        'lastname',
        'company',
        'type',
        'description',
        'country_id',
        'zip',
        'city',
        'state',
        'address',
        'assigned_id',
        'status_id',
        'source_id',
        'email',
        'website',
        'phone',
        'is_enabled',
        'default_language',
        'addedfrom',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_enabled' => 'boolean',
        // Uncomment when using enum for type
        // 'type' => ContactType::class,
    ];

    /**
     * Get the full name as an attribute
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->firstname . ' ' . $this->lastname,
        );
    }

    /**
     * Get the formatted phone number
     */
    protected function formattedPhone(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->phone;
            },
        );
    }

    /**
     * Get contact status relationship
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * Get contact source relationship
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get assigned user relationship
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    /**
     * Get contact notes relationship
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class);
    }

    /**
     * Alias for assignedTo for backward compatibility
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    /**
     * Scope for active contacts
     */
    public function scopeActive($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope for contacts by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to find contacts by phone
     */
    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', $phone)
            ->orWhere('phone', '+' . $phone);
    }
}
