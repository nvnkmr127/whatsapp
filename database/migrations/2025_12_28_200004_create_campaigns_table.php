<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('template_name')->nullable(); // If using WhatsApp Template
            $table->json('template_variables')->nullable();

            $table->unsignedInteger('total_contacts')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('del_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);

            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'processing', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
