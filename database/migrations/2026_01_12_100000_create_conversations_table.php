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
        // 1. Create Conversations Table
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['new', 'open', 'waiting_reply', 'closed', 'blocked'])->default('new');

            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['team_id', 'contact_id', 'status']);
        });

        // 2. Add conversation_id to Messages
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('contact_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });

        Schema::dropIfExists('conversations');
    }
};
