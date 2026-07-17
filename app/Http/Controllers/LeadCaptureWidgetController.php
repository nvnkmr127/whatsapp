<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneNumberHelper;
use App\Models\LeadCaptureWidget;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;

class LeadCaptureWidgetController extends Controller
{
    /**
     * Redirect to WhatsApp and track scan.
     */
    public function show($slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->firstOrFail();

        $widget->increment('scan_count');

        return redirect()->away($widget->wa_me_link);
    }

    /**
     * Return QR code SVG.
     */
    public function qrImage($slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->firstOrFail();

        $color = $this->hexToRgb($widget->qr_color ?: '#000000');
        $bgColor = $this->hexToRgb($widget->qr_bg_color ?: '#ffffff');

        $renderer = new ImageRenderer(
            new RendererStyle(300, 1, null, null, Fill::uniformColor(
                new Rgb($bgColor[0], $bgColor[1], $bgColor[2]),
                new Rgb($color[0], $color[1], $color[2])
            )),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString(route('qr.show', $slug));

        return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }

    /** @return array{0: int, 1: int, 2: int} [r, g, b] */
    private function hexToRgb($hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return sscanf($hex, '%02x%02x%02x');
    }

    /**
     * Store lead and redirect to WhatsApp.
     */
    public function lead(Request $request, $slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->firstOrFail();

        // Best effort: a valid phone gives us a real contact now; without one,
        // the [ref:slug] tracker in the wa.me message identifies them when they text.
        try {
            $phone = PhoneNumberHelper::normalize((string) $request->input('phone'));

            \App\Models\Contact::updateOrCreate(
                ['team_id' => $widget->team_id, 'phone_number' => $phone],
                [
                    'name' => $request->input('name') ?: 'Lead from '.$widget->name,
                    'email' => $request->input('email'),
                    'lead_source_id' => \App\Models\LeadSource::firstOrCreate(
                        ['team_id' => $widget->team_id, 'name' => 'Growth: '.$widget->name],
                        ['type' => 'custom']
                    )->id,
                ]
            );
        } catch (\InvalidArgumentException) {
            // No usable phone number — skip contact creation
        }

        $widget->increment('conversion_count');

        return redirect()->away($widget->wa_me_link);
    }

    /**
     * Get widget configuration as JSON. CORS handled by config/cors.php.
     */
    public function config($slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->firstOrFail();

        return response()->json([
            'active' => (bool) $widget->is_active,
            'slug' => $widget->slug,
            'name' => $widget->name,
            'text' => $widget->button_text,
            'color' => $widget->widget_color,
            'collect_name' => (bool) $widget->collect_name,
            'collect_email' => (bool) $widget->collect_email,
            'collect_phone' => (bool) $widget->collect_phone,
            'brand_name' => $widget->brand_name,
            'brand_subtitle' => $widget->brand_subtitle,
            'brand_logo' => $widget->brand_logo_url,
            'welcome_message' => $widget->welcome_message,
            'footer_text' => $widget->footer_text,
            'placeholder_name' => $widget->placeholder_name,
            'placeholder_email' => $widget->placeholder_email,
            'placeholder_phone' => $widget->placeholder_phone,
            'position' => $widget->position,
            'bottom_margin' => $widget->bottom_margin,
            'side_margin' => $widget->side_margin,
            'border_radius' => $widget->border_radius,
            'open_by_default' => (bool) $widget->open_by_default,
            'show_mobile' => (bool) $widget->show_on_mobile,
            'show_desktop' => (bool) $widget->show_on_desktop,
            'business_hours' => $widget->business_hours,
            'page_targeting' => $widget->page_targeting,
            'time_on_page' => (int) $widget->time_on_page,
            'exit_intent' => (bool) $widget->exit_intent,
            'wa_url' => $widget->wa_me_link,
            'lead_url' => route('qr.lead', $widget->slug),
            'click_url' => route('qr.click', $widget->slug),
        ]);
    }

    public function trackClick($slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->first();
        if ($widget) {
            $widget->increment('click_count');
        }

        return response()->json(['status' => 'success']);
    }
}
