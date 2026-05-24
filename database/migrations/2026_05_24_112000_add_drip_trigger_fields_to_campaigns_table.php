<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'drip_trigger_type')) {
                $table->string('drip_trigger_type')->default('instant')->after('campaign_type');
            }
            if (! Schema::hasColumn('campaigns', 'drip_send_to_existing')) {
                $table->boolean('drip_send_to_existing')->default(true)->after('drip_trigger_type');
            }
            $table->enum('status', [
                'draft',
                'scheduled',
                'preparing',
                'processing',
                'sending',
                'sent',
                'completed',
                'cancelled',
                'failed',
                'active',
                'paused'
            ])->default('draft')->change();
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['drip_trigger_type', 'drip_send_to_existing']);
            $table->enum('status', [
                'draft',
                'scheduled',
                'preparing',
                'processing',
                'sending',
                'sent',
                'completed',
                'cancelled',
                'failed'
            ])->default('draft')->change();
        });
    }
};
