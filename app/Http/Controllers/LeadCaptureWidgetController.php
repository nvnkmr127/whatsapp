<?php

namespace App\Http\Controllers;

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
    public function qr($slug)
    {
        return $this->qrImage($slug);
    }

    public function qrImage($slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->firstOrFail();

        $color = $this->hexToRgb($widget->qr_color ?: '#000000');
        $bgColor = $this->hexToRgb($widget->qr_bg_color ?: '#ffffff');

        $renderer = new ImageRenderer(
            new RendererStyle(300, 1, null, null, Fill::uniformColor(
                new Rgb($bgColor['r'], $bgColor['g'], $bgColor['b']),
                new Rgb($color['r'], $color['g'], $color['b'])
            )),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString(route('qr.show', $slug));

        return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }

    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    /**
     * Store lead and redirect to WhatsApp.
     */
    public function lead(Request $request, $slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->firstOrFail();
        $team = $widget->team;

        $name = $request->input('name');
        $email = $request->input('email');
        $phone = $request->input('phone');

        if ($name || $email || $phone) {
            // Create or update contact
            $contact = \App\Models\Contact::updateOrCreate(
                ['team_id' => $team->id, 'phone' => $phone ?: 'unknown'],
                [
                    'name' => $name ?: 'Lead from '.$widget->name,
                    'email' => $email,
                    'lead_source_id' => \App\Models\LeadSource::firstOrCreate(
                        ['team_id' => $team->id, 'name' => 'Growth: '.$widget->name],
                        ['type' => 'custom']
                    )->id,
                ]
            );
        }

        $widget->increment('conversion_count');

        return redirect()->away($widget->wa_me_link);
    }

    /**
     * Get widget configuration as JSON.
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
            'brand_name' => $widget->brand_name,
            'brand_subtitle' => $widget->brand_subtitle,
            'brand_logo' => $widget->brand_logo_url,
            'welcome_message' => $widget->welcome_message,
            'footer_text' => $widget->footer_text,
            'placeholder_name' => $widget->placeholder_name,
            'placeholder_email' => $widget->placeholder_email,
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
        ])->header('Access-Control-Allow-Origin', '*');
    }

    public function trackClick($slug)
    {
        $widget = LeadCaptureWidget::where('slug', $slug)->first();
        if ($widget) {
            $widget->increment('click_count');
        }

        return response()->json(['status' => 'success'])->header('Access-Control-Allow-Origin', '*');
    }
}
