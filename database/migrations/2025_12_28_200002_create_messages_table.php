<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->string('whatsapp_message_id')->nullable()->index();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('type')->default('text'); // text, image, template, interactive

            $table->text('content')->nullable(); // Text body
            $table->json('metadata')->nullable(); // Holds template params, media URL, etc.

            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('queued');
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
