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
        Schema::create('tenant_backups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['tenant', 'global'])->default('tenant');
            $table->string('filename');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'pruned'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('pruned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
    }
};
