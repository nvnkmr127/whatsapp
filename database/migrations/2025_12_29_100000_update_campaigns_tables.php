<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'campaign_name')) {
                $table->string('campaign_name')->after('id'); // Add if missing
            }
            if (!Schema::hasColumn('campaigns', 'template_id')) {
                $table->string('template_id')->nullable()->after('campaign_name');
            }
            if (!Schema::hasColumn('campaigns', 'template_name')) {
                $table->string('template_name')->nullable()->after('template_id');
            }
            if (!Schema::hasColumn('campaigns', 'header_params')) {
                $table->json('header_params')->nullable()->after('status');
            }
            if (!Schema::hasColumn('campaigns', 'body_params')) {
                $table->json('body_params')->nullable()->after('header_params');
            }
            if (!Schema::hasColumn('campaigns', 'footer_params')) {
                $table->json('footer_params')->nullable()->after('body_params');
            }
            if (!Schema::hasColumn('campaigns', 'media_url')) {
                $table->string('media_url')->nullable()->after('footer_params');
            }
            if (!Schema::hasColumn('campaigns', 'total_contacts')) {
                $table->integer('total_contacts')->default(0)->after('media_url');
            }
            if (!Schema::hasColumn('campaigns', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('total_contacts');
            }
        });

        if (!Schema::hasTable('campaign_details')) {
            Schema::create('campaign_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
                $table->foreignId('contact_id')->constrained()->onDelete('cascade');
                $table->string('phone');
                $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
                $table->string('message_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('campaign_details');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_name',
                'template_id',
                'template_name',
                'header_params',
                'body_params',
                'footer_params',
                'media_url',
                'total_contacts',
                'scheduled_at'
            ]);
        });
    }
};
