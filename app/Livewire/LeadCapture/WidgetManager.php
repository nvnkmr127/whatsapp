<?php

namespace App\Livewire\LeadCapture;

use App\Models\LeadCaptureWidget;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Growth Tools')]
class WidgetManager extends Component
{
    /** Every widget setting managed by the create/edit form, in one place. */
    private const FIELDS = [
        'name', 'prefilled_message', 'button_text', 'widget_color',
        'collect_name', 'collect_email', 'collect_phone',
        'brand_name', 'brand_subtitle', 'brand_logo_url',
        'welcome_message', 'footer_text',
        'placeholder_name', 'placeholder_email', 'placeholder_phone',
        'qr_color', 'qr_bg_color',
        'position', 'bottom_margin', 'side_margin', 'border_radius',
        'open_by_default', 'show_on_mobile', 'show_on_desktop',
        'page_targeting', 'time_on_page', 'exit_intent', 'business_hours',
    ];

    public $showCreateModal = false;

    public $showCodeModal = false;

    public $editingWidgetId;

    public $codeWidgetId;

    // Form fields — declared defaults are the create-form defaults
    public $name;

    public $prefilled_message;

    public $button_text = 'Chat with us';

    public $widget_color = '#25D366';

    public $collect_name = false;

    public $collect_email = false;

    public $collect_phone = false;

    public $brand_name;

    public $brand_subtitle;

    public $brand_logo_url;

    public $welcome_message;

    public $footer_text;

    public $placeholder_name;

    public $placeholder_email;

    public $placeholder_phone;

    public $qr_color = '#000000';

    public $qr_bg_color = '#ffffff';

    public $position = 'right';

    public $bottom_margin = 30;

    public $side_margin = 30;

    public $border_radius = 50;

    public $open_by_default = false;

    public $show_on_mobile = true;

    public $show_on_desktop = true;

    public $page_targeting;

    public $time_on_page = 0;

    public $exit_intent = false;

    public $business_hours = []; // Format: ['mon' => ['start' => '09:00', 'end' => '17:00'], ...]

    protected $rules = [
        'name' => 'required|min:3',
        'prefilled_message' => 'nullable|string',
        'button_text' => 'required',
        'widget_color' => 'required',
    ];

    #[Computed]
    public function selectedWidget()
    {
        return $this->codeWidgetId ? LeadCaptureWidget::find($this->codeWidgetId) : null;
    }

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
        $this->reset([...self::FIELDS, 'editingWidgetId']);
        $this->showCreateModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = collect($this->only(self::FIELDS))
            ->put('team_id', auth()->user()->currentTeam->id)
            ->all();

        if ($this->editingWidgetId) {
            LeadCaptureWidget::where('team_id', auth()->user()->currentTeam->id)
                ->findOrFail($this->editingWidgetId)
                ->update($data);
        } else {
            $data['slug'] = Str::slug($this->name).'-'.Str::random(5);
            LeadCaptureWidget::create($data);
        }

        $this->showCreateModal = false;
        $this->reset([...self::FIELDS, 'editingWidgetId']);

        session()->flash('message', 'Widget saved successfully.');
    }

    public function edit($id)
    {
        $widget = $this->findForTeam($id);
        $this->editingWidgetId = $id;
        $this->fill($widget->only(self::FIELDS));
        $this->qr_color = $widget->qr_color ?: '#000000';
        $this->qr_bg_color = $widget->qr_bg_color ?: '#ffffff';
        $this->business_hours = $widget->business_hours ?: [];
        $this->showCreateModal = true;
    }

    public function toggleActive($id)
    {
        $widget = $this->findForTeam($id);
        $widget->update(['is_active' => ! $widget->is_active]);
    }

    public function delete($id)
    {
        $this->findForTeam($id)->delete();
        session()->flash('message', 'Widget deleted.');
    }

    public function openCodeModal($id)
    {
        $this->codeWidgetId = $id;
        $this->showCodeModal = true;
    }

    private function findForTeam($id): LeadCaptureWidget
    {
        return LeadCaptureWidget::where('team_id', auth()->user()->currentTeam->id)->findOrFail($id);
    }
}
