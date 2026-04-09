<?php

namespace Tests\Unit;

use App\Enums\EmailUseCase;
use App\Services\Email\EmailDispatcher;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! \Illuminate\Support\Facades\Schema::hasTable('smtp_configs')) {
            \Illuminate\Support\Facades\Schema::create('smtp_configs', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('host')->nullable();
                $table->integer('port')->nullable();
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->string('encryption')->nullable();
                $table->string('from_address')->nullable();
                $table->string('from_name')->nullable();
                $table->timestamps();
            });
        }
        if (! \Illuminate\Support\Facades\Schema::hasTable('email_logs')) {
            \Illuminate\Support\Facades\Schema::create('email_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('recipient');
                $table->string('use_case');
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('subject');
                $table->string('status');
                $table->string('provider_name');
                $table->string('message_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->string('failure_type')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_dispatcher_uses_transactional_mailer_for_otp()
    {
        Mail::fake();

        $dispatcher = app(EmailDispatcher::class);
        $mailable = new class extends Mailable
        {
            public function build()
            {
                return $this->html('test');
            }
        };

        $dispatcher->send('test@example.com', EmailUseCase::OTP, $mailable);

        Mail::assertSent(\App\Mail\DynamicSystemMail::class, function ($mail) {
            return $mail->mailer === 'transactional';
        });
    }

    public function test_dispatcher_uses_marketing_mailer_for_marketing()
    {
        Mail::fake();

        $dispatcher = app(EmailDispatcher::class);
        $mailable = new class extends Mailable
        {
            public function build()
            {
                return $this->html('test');
            }
        };

        $dispatcher->send('test@example.com', EmailUseCase::MARKETING, $mailable);

        Mail::assertSent(\App\Mail\DynamicSystemMail::class, function ($mail) {
            return $mail->mailer === 'marketing';
        });
    }

    public function test_all_use_cases_have_valid_mailers_defined_in_config()
    {
        $cases = EmailUseCase::cases();
        $configMailers = array_keys(config('mail.mailers'));

        foreach ($cases as $case) {
            $mailer = $case->getMailer();
            $this->assertContains($mailer, $configMailers, "Mailer '$mailer' for case '{$case->name}' is not defined in config/mail.php");
        }
    }
}
