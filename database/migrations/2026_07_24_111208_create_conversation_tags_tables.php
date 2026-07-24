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
        Schema::create('conversation_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color_code')->default('bg-slate-200 text-slate-800');
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('conversation_tag_pivot', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['conversation_id', 'conversation_tag_id'], 'conv_tag_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_tag_pivot');
        Schema::dropIfExists('conversation_tags');
    }
};
