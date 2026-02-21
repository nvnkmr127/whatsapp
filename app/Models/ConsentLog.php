<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * ConsentLog — Append-Only Compliance Audit Trail
 * ═══════════════════════════════════════════════
 * Rows are immutable after creation. Use ConsentRegistry for
 * authoritative consent-state lookups; this table is for BI / audit queries.
 */
class ConsentLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // ── Immutability enforcement ───────────────────────────────────────────
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException(
                'ConsentLog is append-only. Create a new row instead of updating an existing one.'
            );
        });

        static::deleting(function () {
            throw new RuntimeException(
                'ConsentLog rows cannot be deleted — this is a GDPR compliance audit trail.'
            );
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
