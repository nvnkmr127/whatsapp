<?php

namespace App\Livewire\Admin\Table;

use App\Models\CampaignDetail;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CampaignExecutedTable extends PowerGridComponent
{
    public string $tableName = 'campaign-executed-table-36k8du-table';

    public $campaign_id;

    public function mount(): void
    {
        parent::mount();
        $this->campaign_id = request()->route('campaignId');
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return CampaignDetail::query()
            ->join('contacts', 'campaign_details.rel_id', '=', 'contacts.id')
            ->where('campaign_id', $this->campaign_id)
            ->where('status', '!=', 1)
            ->select('campaign_details.*', 'contacts.phone');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('campaign_id')
            ->add('rel_id', fn ($model) => ($contact = Contact::find($model->rel_id)) ?
                $contact->firstname . ' ' . $contact->lastname :
                $model->rel_id)
            ->add('rel_type')
            ->add('header_message')
            ->add(
                'body_message',
                fn ($model) => ($model->header_message ? $model->header_message . "\n\n" : '') .
                    ($model->body_message ?? '') .
                    ($model->footer_message ? "\n\n" . $model->footer_message : '')
            )
            ->add('footer_message')
            ->add(
                'status',
                fn ($message) => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' .
                    match ($message->status) {
                        0       => 'bg-red-100 text-red-800 dark:text-red-400 dark:bg-red-900/20',
                        1       => 'bg-yellow-100 text-yellow-800 dark:text-yellow-400 dark:bg-yellow-900/20',
                        2       => 'bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                    } .
                    '">' . match ($message->status) {
                        0       => 'Failed',
                        1       => 'Pending',
                        2       => 'Success',
                        default => 'N/A'
                    } . '</span>'
            )
            ->add('response_message')
            ->add('whatsapp_id')
            ->add(
                'message_status',
                fn ($model) => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' .
                    match ($model->message_status) {
                        'sent'      => 'bg-blue-100 text-blue-800 dark:text-blue-400 dark:bg-blue-900/20',
                        'delivered' => 'bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20',
                        'read'      => 'bg-purple-100 text-purple-800 dark:text-purple-400 dark:bg-purple-900/20',
                        'failed'    => 'bg-red-100 text-red-800 dark:text-red-400 dark:bg-red-900/20',
                        'pending'   => 'bg-yellow-100 text-yellow-800 dark:text-yellow-400 dark:bg-yellow-900/20',
                        default     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                    } .
                    '">' . ucfirst($model->message_status ?? 'N/A') . '</span>'
            )
            ->add('created_at');
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(t('name'), 'rel_id')
                ->sortable()
                ->searchableRaw("CONCAT(firstname, ' ', lastname) LIKE ?")
                ->searchable(),

            Column::make(t('phone'), 'phone')
                ->sortable()
                ->searchable(),
            Column::make(t('message'), 'body_message')
                ->sortable()
                ->searchable()
                ->bodyAttribute('style', 'width: calc(25 * 3ch); word-wrap: break-word; white-space: normal; line-height: 1.8;'),
            Column::make(t('sent_status'), 'status')
                ->sortable()
                ->searchable(),
        ];
    }
}
