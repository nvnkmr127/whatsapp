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
        Schema::create('broadcast_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->json('payload');
            $table->string('status')->default('pending')->index(); // pending, processing, completed, failed
            $table->string('group_name')->nullable()->index(); // For consumer group isolation
            $table->timestamp('locked_at')->nullable(); // For distributed consumption
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_events');
    }
};
