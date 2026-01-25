<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type'); // 'limit_increase', 'feature_enable', 'grace_extension'
            $table->string('key'); // 'message_limit', 'ai_access', etc.
            $table->text('value');

            $table->string('reason');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Can void an override by deleting it
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_overrides');
    }
};
