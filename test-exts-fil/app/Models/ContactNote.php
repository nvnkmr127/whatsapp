<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property int                             $contact_id
 * @property string                          $notes_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contact $contact
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote whereNotesDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactNote whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContactNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'notes_description',
    ];

    /**
     * Get the contact associated with the note.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
