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
        Schema::create('webhook_payloads', function (Blueprint $table) {
            $table->id();
            $table->string('waba_id')->nullable()->index();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->string('status')->default('pending'); // pending, processing, processed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_payloads');
    }
};
