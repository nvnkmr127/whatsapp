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
        Schema::create('automation_step_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_run_id')->constrained('automation_runs')->onDelete('cascade');
            $table->string('node_id');
            $table->string('execution_key')->unique(); // run_id + node_id + attempt
            $table->string('status'); // success, failed
            $table->json('output_state')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['automation_run_id', 'node_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_step_ledger');
    }
};
