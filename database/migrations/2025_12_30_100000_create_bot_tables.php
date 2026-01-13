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
        Schema::create('message_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rel_type')->nullable(); // Defaults for contact/lead/etc
            $table->text('reply_text')->nullable();
            $table->integer('reply_type')->default(1); // 1=text, 2=image, etc
            $table->json('trigger')->nullable(); // Keywords
            $table->string('bot_header')->nullable();
            $table->string('bot_footer')->nullable();

            // Buttons logic (Reference has specific fields for buttons)
            $table->string('button1')->nullable();
            $table->string('button1_id')->nullable();
            $table->string('button2')->nullable();
            $table->string('button2_id')->nullable();
            $table->string('button3')->nullable();
            $table->string('button3_id')->nullable();
            $table->string('button_name')->nullable(); // For Call to Action or link
            $table->string('button_url')->nullable();

            $table->unsignedBigInteger('addedfrom')->nullable();
            $table->boolean('is_bot_active')->default(true);
            $table->integer('sending_count')->default(0);
            $table->string('filename')->nullable(); // For media
            $table->timestamps();
        });

        Schema::create('template_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rel_type')->nullable();
            $table->string('template_id'); // ID from whatsapp_templates table (usually a string ID from Meta or local ID?) 
            // Reference query joins on 'template_id' column of whatsapp_templates. 
            // My WhatsappTemplate migration uses 'template_id' as Meta ID (string/bigint) and 'id' as local PK.
            // Reference Join: template_bots.template_id = whatsapp_templates.template_id
            // So this should be the Meta Template ID string.

            $table->json('header_params')->nullable();
            $table->json('body_params')->nullable();
            $table->json('footer_params')->nullable();
            $table->string('filename')->nullable();
            $table->json('trigger')->nullable();
            $table->integer('reply_type')->default(1);
            $table->boolean('is_bot_active')->default(true);
            $table->integer('sending_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_bots');
        Schema::dropIfExists('message_bots');
    }
};
