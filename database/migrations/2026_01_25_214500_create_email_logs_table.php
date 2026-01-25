<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient')->index();
            $table->string('use_case');
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('subject');
            $table->string('status')->default('sent')->index(); // sent, delivered, failed, bounced
            $table->foreignUuid('smtp_config_id')->nullable()->constrained('smtp_configs')->nullOnDelete();
            $table->string('provider_name')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('failure_type')->nullable()->comment('network, authentication, smtp_error, validation');
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
