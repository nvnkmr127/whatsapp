<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templateData = [
            [
                'name'                => 'SMTP Test Mail',
                'subject'             => 'SMTP Test Mail',
                'message'             => '<p>This is a test email to confirm that your SMTP settings are correctly configured.</p><p>If you have received this email, your SMTP setup is working properly.</p><p>If you did not request this test or face any issues, please review your SMTP configuration or contact support at <a href="mailto:{company_email}">{company_email}</a>.</p>',
                'slug'                => 'smtp-test-mail',
                'merge_fields_groups' => ['other-group'],
                'is_active'           => 1,
            ],
            [
                'name'                => 'Email Confirmation',
                'subject'             => 'Email Confirmation',
                'message'             => '<p>Thank you for signing up with {site_name}, {first_name} {last_name}!</p><p>We\'re thrilled to have you on board. Before you get started, we need to verify your email address to ensure the security of your account.</p><p>Please click the button below to verify your email:</p>',
                'slug'                => 'email-confirmation',
                'merge_fields_groups' => ['other-group', 'user-group'],
                'is_active'           => 1,
            ],
            [
                'name'                => 'Welcome Email',
                'subject'             => 'Welcome to {site_name}!',
                'message'             => '<p>Dear {first_name} {last_name},</p><p>Welcome to {site_name}! We\'re excited to have you on board. 🚀</p><p>Get ready to explore our amazing features and make your life easier.</p><p>If you have any questions, our support team at <a href="mailto:{company_email}">{company_email}</a> is always here to help.</p><p>Start your journey here: <a href="{base_url}">{base_url}</a></p><p>Looking forward to seeing you thrive!</p>',
                'slug'                => 'welcome-mail',
                'merge_fields_groups' => ['other-group', 'user-group'],
                'is_active'           => 1,
            ],
            [
                'name'                => 'Password Reset',
                'subject'             => 'Password Reset Request',
                'message'             => '<p>Hello {first_name} {last_name},</p><p>We received a request to reset your password for your {site_name} account.</p><p>If you made this request, click the button below to reset your password:</p><p><a href="{reset_link}">Reset Password</a></p><p>If you did not request a password reset, please ignore this email or contact support at <a href="mailto:{company_email}">{company_email}</a>.</p>',
                'slug'                => 'password-reset',
                'merge_fields_groups' => ['other-group', 'user-group'],
                'is_active'           => 1,
            ],
            [
                'name'                => 'New Contact Assigned',
                'subject'             => '📌 New Contact Assigned to You',
                'message'             => '<p>Hi {first_name} {last_name},</p><p>A new contact has been assigned to you. Here are the details:</p><ul><li><strong>Contact Name:</strong> {contact_first_name} {contact_last_name}</li><li><strong>Email:</strong> {contact_email}</li><li><strong>Phone:</strong> {contact_phone_number}</li><li><strong>Assigned By:</strong> {assigned_by}</li></ul><p>Please reach out to them promptly and ensure a smooth follow-up.</p><p>If you have any questions, feel free to get in touch.</p><p><strong>Best regards,</strong><br>{site_name}</p>',
                'slug'                => 'new-contact-assigned',
                'merge_fields_groups' => ['other-group', 'user-group', 'contact-group'],
                'is_active'           => 1,
            ],

        ];

        foreach ($templateData as $data) {
            DB::table('email_templates')->updateOrInsert(
                ['slug' => $data['slug']],
                [
                    'name'                => $data['name'],
                    'subject'             => $data['subject'],
                    'slug'                => $data['slug'],
                    'message'             => $data['message'],
                    'is_active'           => $data['is_active'],
                    'merge_fields_groups' => json_encode($data['merge_fields_groups']),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]
            );
        }
    }
}
