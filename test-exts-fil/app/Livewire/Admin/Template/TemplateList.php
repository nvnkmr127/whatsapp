<?php

namespace App\Livewire\Admin\Template;

use App\Traits\WhatsApp;
use Livewire\Component;

class TemplateList extends Component
{
    use WhatsApp;

    public $isDisconnected;

    public function mount()
    {
        if (! checkPermission('template.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    public function loadTemplate()
    {
        if (checkPermission('template.load_template')) {
            try {
                if (
                    empty(get_setting('whatsapp.is_webhook_connected')) || empty(get_setting('whatsapp.is_whatsmark_connected')) || empty(get_setting('whatsapp.wm_default_phone_number'))
                ) {
                    $this->isDisconnected = true;

                    return;
                }

                $response = $this->loadTemplatesFromWhatsApp();
                $this->notify([
                    'message' => $response['message'],
                    'type'    => $response['status'] ? 'success' : 'danger',
                ]);

                $this->dispatch('pg:eventRefresh-whatspp-template-table-sgz2iu-table', [], 'window');
            } catch (\Exception $e) {
                whatsapp_log('Error loading WhatsApp templates: ' . $e->getMessage(), 'error', [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], $e);

                $this->notify([
                    'message' => t('template_load_failed') . ': ' . $e->getMessage(),
                    'type'    => 'danger',
                ]);
            }
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-whatspp-template-table-sgz2iu-table');
    }

    public function render()
    {
        return view('livewire.admin.template.template-list');
    }
}
