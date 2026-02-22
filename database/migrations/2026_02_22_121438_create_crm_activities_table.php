<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // note, call, email, meeting, status_change, etc.
            $table->text('content')->nullable();
            $table->string('outcome')->nullable(); // For calls/meetings: answered, no_answer, busy, etc.
            $table->integer('duration_minutes')->nullable();
            $table->dateTime('performed_at');
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin who performed action
            
            // Polymorphic relation to Team or User
            $table->morphs('related_to');
            
            $table->json('metadata')->nullable(); // Extra data
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
