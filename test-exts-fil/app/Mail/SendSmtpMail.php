<?php

namespace App\Mail;

use App\Services\MergeFields;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    protected array $data;

    public function mount()
    {
        if (! checkPermission('email_template.view')) {
            return redirect()->route('admin.dashboard');
        }
    }

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $content = $this->data['content'];

        if ($templateTitle = getArrayItem('slug', $content)) {

            $template = mailTemplate($templateTitle);

            $user   = $content['user']   ?? null;
            $groups = $content['groups'] ?? t('other_group');

            $content['subject'] = app(MergeFields::class)->parseTemplates(
                $groups,
                $template->subject,
                ['user' => $user]
            );

            $content['body'] = app(MergeFields::class)->parseTemplates(
                $groups,
                $template->message,
                ['user' => $user]
            );
        } else {

            $content['subject'] = $content['subject'] ?? t('default_subject');
            $content['body']    = $content['body']    ?? t('default_body_content');
        }

        return $this->subject($content['subject'])
            ->view('components.mail', [
                'title'      => $content['subject'],
                'body'       => $content['body'],
                'user'       => $content['user']        ?? null,
                'actionUrl'  => $content['action_url']  ?? null,
                'actionText' => $content['action_text'] ?? null,
                'greeting'   => $content['greeting']    ?? t('hello') . ' ' . ($content['user']->name ?? t('there')) . ',',
            ]);
    }
}
