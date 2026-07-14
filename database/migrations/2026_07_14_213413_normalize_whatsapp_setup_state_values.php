<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Collapse the mixed uppercase/lowercase state vocabulary into the
     * canonical lowercase IntegrationState values.
     */
    public function up(): void
    {
        $map = [
            'NOT_CONFIGURED' => 'disconnected',
            'DISCONNECTED' => 'disconnected',
            'ACTIVE' => 'ready',
            'DEGRADED' => 'degraded',
            'SUSPENDED' => 'suspended',
            'connected' => 'ready',
            'TOKEN_EXPIRED' => 'token_expired',
        ];

        foreach ($map as $from => $to) {
            DB::table('teams')->where('whatsapp_setup_state', $from)->update(['whatsapp_setup_state' => $to]);
        }
    }

    public function down(): void
    {
        // One-way normalization; original mixed-case values are not restorable.
    }
};
