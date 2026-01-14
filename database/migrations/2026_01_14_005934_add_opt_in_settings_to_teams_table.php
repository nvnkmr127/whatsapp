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
        Schema::table('teams', function (Blueprint $table) {
            $table->json('opt_in_keywords')->nullable();
            $table->json('opt_out_keywords')->nullable();
            $table->text('opt_in_message')->nullable();
            $table->text('opt_out_message')->nullable();
            $table->boolean('opt_in_message_enabled')->default(false);
            $table->boolean('opt_out_message_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'opt_in_keywords',
                'opt_out_keywords',
                'opt_in_message',
                'opt_out_message',
                'opt_in_message_enabled',
                'opt_out_message_enabled',
            ]);
        });
    }
};
