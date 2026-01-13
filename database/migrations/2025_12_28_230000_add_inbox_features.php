<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Assignments
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
        });

        // 2. Internal Notes
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Author
            $table->text('body');
            $table->string('type')->default('note'); // 'note', 'system'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
        });

        Schema::dropIfExists('notes');
    }
};
