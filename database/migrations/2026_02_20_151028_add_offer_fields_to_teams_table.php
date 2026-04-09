<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds three columns that the Offer Eligibility Engine uses to determine
     * whether a team may still benefit from the launch offer:
     *
     *  - offer_claimed_at         : timestamp set the first time the team
     *                               activates/uses the offer (one-time, non-null = claimed).
     *  - offer_excluded           : boolean toggled by a Super Admin to manually
     *                               block this team from ever receiving the offer.
     *  - offer_converted_churned  : boolean set when a team paid (converted) and
     *                               then cancelled/churned, so the offer cannot be
     *                               reused on a fresh trial.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('offer_claimed_at')->nullable()->after('subscription_ends_at');
            $table->boolean('offer_excluded')->default(false)->after('offer_claimed_at');
            $table->boolean('offer_converted_churned')->default(false)->after('offer_excluded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['offer_claimed_at', 'offer_excluded', 'offer_converted_churned']);
        });
    }
};
