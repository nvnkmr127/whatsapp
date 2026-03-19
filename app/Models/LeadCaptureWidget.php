<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadCaptureWidget extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'prefilled_message',
        'button_text',
        'widget_color',
        'collect_name',
        'collect_email',
        'brand_name',
        'brand_subtitle',
        'brand_logo_url',
        'welcome_message',
        'footer_text',
        'placeholder_name',
        'placeholder_email',
        'qr_color',
        'qr_bg_color',
        'position',
        'bottom_margin',
        'side_margin',
        'border_radius',
        'open_by_default',
        'show_on_mobile',
        'show_on_desktop',
        'business_hours',
        'page_targeting',
        'time_on_page',
        'exit_intent',
        'scan_count',
        'click_count',
        'conversion_count',
        'is_active',
    ];

    protected $casts = [
        'collect_name' => 'boolean',
        'collect_email' => 'boolean',
        'open_by_default' => 'boolean',
        'show_on_mobile' => 'boolean',
        'show_on_desktop' => 'boolean',
        'exit_intent' => 'boolean',
        'is_active' => 'boolean',
        'business_hours' => 'array',
    ];



    public function getWaMeLinkAttribute()
    {
        $phone = $this->team->whatsapp_phone_display;
        if (!$phone) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phone);
        $message = $this->prefilled_message;

        // Append unique tracker to track conversion on webhook
        $message .= " [ref:{$this->slug}]";

        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }

    public function getQrCodeUrlAttribute()
    {
        return route('qr.show', $this->slug);
    }
}
