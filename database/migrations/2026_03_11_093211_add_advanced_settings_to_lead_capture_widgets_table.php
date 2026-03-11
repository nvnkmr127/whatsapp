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
        Schema::table('lead_capture_widgets', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('collect_email');
            $table->string('brand_subtitle')->nullable()->after('brand_name');
            $table->string('brand_logo_url')->nullable()->after('brand_subtitle');
            $table->string('position')->default('right')->after('brand_logo_url');
            $table->integer('bottom_margin')->default(30)->after('position');
            $table->integer('side_margin')->default(30)->after('bottom_margin');
            $table->integer('border_radius')->default(50)->after('side_margin');
            $table->boolean('open_by_default')->default(false)->after('border_radius');
            $table->boolean('show_on_mobile')->default(true)->after('open_by_default');
            $table->boolean('show_on_desktop')->default(true)->after('show_on_mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_capture_widgets', function (Blueprint $table) {
            //
        });
    }
};
