<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove any duplicate tag assignments
        DB::statement('
            DELETE t1 FROM contact_tag_pivot t1
            INNER JOIN contact_tag_pivot t2 
            WHERE t1.id > t2.id 
            AND t1.contact_id = t2.contact_id 
            AND t1.tag_id = t2.tag_id
        ');

        // Add unique constraint to prevent future duplicates
        Schema::table('contact_tag_pivot', function (Blueprint $table) {
            $table->unique(['contact_id', 'tag_id'], 'unique_contact_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_tag_pivot', function (Blueprint $table) {
            $table->dropUnique('unique_contact_tag');
        });
    }
};
