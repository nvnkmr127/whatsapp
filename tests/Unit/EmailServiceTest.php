<?php

namespace Tests\Unit;

use App\Enums\EmailUseCase;
use App\Services\Email\EmailDispatcher;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Mail\Mailable;

class EmailServiceTest extends TestCase
{
    public function test_dispatcher_uses_transactional_mailer_for_otp()
    {
        Mail::fake();

        $dispatcher = new EmailDispatcher();
        $mailable = new class extends Mailable {
            public function build()
            {
                return $this->html('test'); }
        };

        $dispatcher->send('test@example.com', EmailUseCase::OTP, $mailable);

        Mail::assertSent(get_class($mailable), function ($mail) {
            return $mail->mailer === 'transactional';
        });
    }

    public function test_dispatcher_uses_marketing_mailer_for_marketing()
    {
        Mail::fake();

        $dispatcher = new EmailDispatcher();
        $mailable = new class extends Mailable {
            public function build()
            {
                return $this->html('test'); }
        };

        $dispatcher->send('test@example.com', EmailUseCase::MARKETING, $mailable);

        Mail::assertSent(get_class($mailable), function ($mail) {
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
