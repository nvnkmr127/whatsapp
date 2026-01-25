<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index()->comment('Values from EmailUseCase enum');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('content_html')->nullable();
            $table->longText('content_text')->nullable();
            $table->json('variable_schema')->nullable()->comment('Defines allowed keys like ["code", "user_name"]');
            $table->boolean('is_locked')->default(false)->comment('If true, slug and schema are immutable');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
