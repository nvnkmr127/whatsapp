<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppSetupAudit extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_setup_audit';

    protected $fillable = [
        'team_id',
        'user_id',
        'action',
        'status',
        'changes',
        'metadata',
        'ip_address',
        'reference_id',
    ];

    protected $casts = [
        'changes' => 'array',
        'metadata' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateReferenceId(): string
    {
        return 'WA-' . strtoupper(substr(uniqid(), -8));
    }
}
