<?php

namespace App\Livewire\LeadCapture;

use Livewire\Component;

use App\Models\LeadCaptureWidget;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;

use Livewire\Attributes\Computed;

#[Title('Growth Tools')]
class WidgetManager extends Component
{
    #[Computed]
    public function selectedWidget()
    {
        return $this->codeWidgetId ? LeadCaptureWidget::find($this->codeWidgetId) : null;
    }
    public $showCreateModal = false;
    public $showCodeModal = false;
    public $name, $prefilled_message, $button_text, $widget_color = '#25D366';
    public $collect_name = false, $collect_email = false;

    // Advanced Settings
    public $brand_name, $brand_subtitle, $brand_logo_url, $welcome_message, $footer_text, $placeholder_name, $placeholder_email;
    public $qr_color = '#000000', $qr_bg_color = '#ffffff';
    public $position = 'right', $bottom_margin = 30, $side_margin = 30, $border_radius = 50;
    public $open_by_default = false, $show_on_mobile = true, $show_on_desktop = true;

    // Automation Triggers
    public $page_targeting, $time_on_page = 0, $exit_intent = false;
    public $business_hours = []; // Format: ['mon' => ['start' => '09:00', 'end' => '17:00'], ...]

    public $editingWidgetId, $codeWidgetId;

    protected $rules = [
        'name' => 'required|min:3',
        'prefilled_message' => 'nullable|string',
        'button_text' => 'required',
        'widget_color' => 'required',
    ];

    public function render()
    {
        $widgets = LeadCaptureWidget::where('team_id', auth()->user()->currentTeam->id)
            ->latest()
            ->get();

        return view('livewire.lead-capture.widget-manager', [
            'widgets' => $widgets,
        ]);
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'prefilled_message', 'button_text', 'widget_color', 'editingWidgetId', 'collect_name', 'collect_email', 'brand_name', 'brand_subtitle', 'brand_logo_url', 'welcome_message', 'footer_text', 'placeholder_name', 'placeholder_email', 'qr_color', 'qr_bg_color', 'position', 'bottom_margin', 'side_margin', 'border_radius', 'open_by_default', 'show_on_mobile', 'show_on_desktop', 'page_targeting', 'time_on_page', 'exit_intent', 'business_hours']);
        $this->qr_color = '#000000';
        $this->qr_bg_color = '#ffffff';
        $this->button_text = 'Chat with us';
        $this->widget_color = '#25D366';
        $this->position = 'right';
        $this->bottom_margin = 30;
        $this->side_margin = 30;
        $this->border_radius = 50;
        $this->show_on_mobile = true;
        $this->show_on_desktop = true;
        $this->time_on_page = 0;
        $this->exit_intent = false;
        $this->showCreateModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'team_id' => auth()->user()->currentTeam->id,
            'name' => $this->name,
            'prefilled_message' => $this->prefilled_message,
            'button_text' => $this->button_text,
            'widget_color' => $this->widget_color,
            'collect_name' => $this->collect_name,
            'collect_email' => $this->collect_email,
            'brand_name' => $this->brand_name,
            'brand_subtitle' => $this->brand_subtitle,
            'brand_logo_url' => $this->brand_logo_url,
            'welcome_message' => $this->welcome_message,
            'footer_text' => $this->footer_text,
            'placeholder_name' => $this->placeholder_name,
            'placeholder_email' => $this->placeholder_email,
            'qr_color' => $this->qr_color,
            'qr_bg_color' => $this->qr_bg_color,
            'position' => $this->position,
            'bottom_margin' => $this->bottom_margin,
            'side_margin' => $this->side_margin,
            'border_radius' => $this->border_radius,
            'open_by_default' => $this->open_by_default,
            'show_on_mobile' => $this->show_on_mobile,
            'show_on_desktop' => $this->show_on_desktop,
            'page_targeting' => $this->page_targeting,
            'time_on_page' => $this->time_on_page,
            'exit_intent' => $this->exit_intent,
            'business_hours' => $this->business_hours,
        ];

        if ($this->editingWidgetId) {
            LeadCaptureWidget::find($this->editingWidgetId)->update($data);
        } else {
            $data['slug'] = Str::slug($this->name) . '-' . Str::random(5);
            LeadCaptureWidget::create($data);
        }

        $this->showCreateModal = false;
        $this->reset(['name', 'prefilled_message', 'button_text', 'widget_color', 'editingWidgetId', 'collect_name', 'collect_email', 'brand_name', 'brand_subtitle', 'brand_logo_url', 'welcome_message', 'footer_text', 'placeholder_name', 'placeholder_email', 'qr_color', 'qr_bg_color', 'position', 'bottom_margin', 'side_margin', 'border_radius', 'open_by_default', 'show_on_mobile', 'show_on_desktop', 'page_targeting', 'time_on_page', 'exit_intent', 'business_hours']);

        session()->flash('message', 'Widget saved successfully.');
    }

    public function edit($id)
    {
        $widget = LeadCaptureWidget::findOrFail($id);
        $this->editingWidgetId = $id;
        $this->name = $widget->name;
        $this->prefilled_message = $widget->prefilled_message;
        $this->button_text = $widget->button_text;
        $this->widget_color = $widget->widget_color;
        $this->collect_name = $widget->collect_name;
        $this->collect_email = $widget->collect_email;
        $this->brand_name = $widget->brand_name;
        $this->brand_subtitle = $widget->brand_subtitle;
        $this->brand_logo_url = $widget->brand_logo_url;
        $this->welcome_message = $widget->welcome_message;
        $this->footer_text = $widget->footer_text;
        $this->placeholder_name = $widget->placeholder_name;
        $this->placeholder_email = $widget->placeholder_email;
        $this->qr_color = $widget->qr_color ?: '#000000';
        $this->qr_bg_color = $widget->qr_bg_color ?: '#ffffff';
        $this->position = $widget->position;
        $this->bottom_margin = $widget->bottom_margin;
        $this->side_margin = $widget->side_margin;
        $this->border_radius = $widget->border_radius;
        $this->open_by_default = $widget->open_by_default;
        $this->show_on_mobile = $widget->show_on_mobile;
        $this->show_on_desktop = $widget->show_on_desktop;
        $this->page_targeting = $widget->page_targeting;
        $this->time_on_page = $widget->time_on_page;
        $this->exit_intent = $widget->exit_intent;
        $this->business_hours = $widget->business_hours ?: [];
        $this->showCreateModal = true;
    }

    public function delete($id)
    {
        LeadCaptureWidget::findOrFail($id)->delete();
        session()->flash('message', 'Widget deleted.');
    }

    public function openCodeModal($id)
    {
        $this->codeWidgetId = $id;
        $this->showCodeModal = true;
    }
}
