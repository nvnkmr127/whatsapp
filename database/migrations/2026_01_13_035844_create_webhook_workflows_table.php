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
        Schema::create('webhook_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('whatsapp_template_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('status')->default(true);
            $table->unsignedInteger('total_triggers')->default(0);
            $table->unsignedInteger('total_delivered')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_workflows');
    }
};
