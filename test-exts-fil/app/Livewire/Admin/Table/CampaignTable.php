<?php

namespace App\Livewire\Admin\Table;

use App\Enums\WhatsAppTemplateRelationType;
use App\Models\Campaign;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CampaignTable extends PowerGridComponent
{
    public string $sortField = 'created_at';

    public string $sortDirection = 'DESC';

    public string $tableName = 'campaign-table-r3hjpl-table';

    public bool $showFilters = false;

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.custom-loading';

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->withoutLoading()
                ->showToggleColumns()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Campaign::query()
            ->select([
                'campaigns.*',
                'whatsapp_templates.template_name',
                DB::raw('(SELECT COUNT(*) FROM campaign_details 
                    WHERE campaign_details.campaign_id = campaigns.id 
                    AND status = 2) as delivered'),
                DB::raw('(SELECT COUNT(*) FROM campaign_details 
                    WHERE campaign_details.campaign_id = campaigns.id 
                    AND message_status = "read") as read_by')
            ])
            ->leftJoin('whatsapp_templates', 'campaigns.template_id', '=', 'whatsapp_templates.template_id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name', function ($campaign) {
                $user = auth()->user();

                $canView   = $user->can('campaigns.show_campaign') || $user->is_admin == 1;
                $canEdit   = $user->can('campaigns.edit')          || $user->is_admin == 1;
                $canDelete = $user->can('campaigns.delete')        || $user->is_admin == 1;

                return '<div class="group relative inline-block min-h-[40px]">
            <a class="dark:text-gray-200 text-indigo-600 dark:hover:text-indigo-400" href="' . route('admin.campaigns.details', ['campaignId' => $campaign->id]) . '">' . $campaign->name . '</a>
                    <!-- Action Links -->
                 <div class="absolute left-0 lg:left-0 top-3 mt-2 pt-1 hidden contact-actions space-x-1 text-xs text-gray-600 dark:text-gray-300">
                ' . ($canView ? '<a href="' . route('admin.campaigns.details', ['campaignId' => $campaign->id]) . '" class="hover:text-blue-600">' . t('view') . '</a>' : '') .
                    ($canView && ($canEdit || $canDelete) ? '<span>|</span>' : '') .
                    ($canEdit ? '<a href="' . route('admin.campaigns.save', ['campaignId' => $campaign->id]) . '" class="hover:text-green-600">' . t('edit') . '</a>' : '') .
                    ($canEdit && $canDelete ? '<span>|</span>' : '') .
                    ($canDelete ? '<button onclick="Livewire.dispatch(\'confirmDelete\', { campaignId: ' . $campaign->id . ' })" class="hover:text-red-600">' . t('delete') . '</button>' : '') .
                    '</div>
            </div>';
            })
            ->add('rel_type', function ($templateBot) {
                $class = $templateBot->rel_type == 'lead'
                ? 'bg-indigo-100 text-indigo-800 dark:text-indigo-400 dark:bg-indigo-900/20'
                : 'bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20';

                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . ucfirst($templateBot->rel_type) . '</span>';
            })
            ->add('sending_count', function ($campaign) {
                return "<span class='text-sm text-center mx-2'>" . $campaign->sending_count . '</span>';
            })
            ->add('delivered', function ($campaign) {
                return "<span class='text-sm text-center mx-5'>" . $campaign->delivered . '</span>';
            })
            ->add('read_by', function ($campaign) {
                return "<span class='text-sm text-center mx-4'>" . $campaign->read_by . '</span>';
            })
            ->add('created_at_formatted', function ($campaign) {
                return '<div class="relative group">
                         <span class="cursor-default"  data-tippy-content="' . format_date_time($campaign->created_at) . '">' . \Carbon\Carbon::parse($campaign->created_at)->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) . '</span>
                        </div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(t('campaign_name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(t('template'), 'template_name')
                ->sortable()
                ->searchable(),

            Column::make(t('relation_type'), 'rel_type')
                ->sortable()
                ->searchable(),
            Column::make(t('total'), 'sending_count')
                ->sortable()
                ->searchable(),

            Column::make(t('delivered_to'), 'delivered')
                ->sortable(),

            Column::make(t('ready_by'), 'read_by')
                ->sortable(),

            Column::make(t('created_at'), 'created_at_formatted', 'created_at')
                ->sortable()
                ->searchable(),

        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('template_name', 'whatsapp_templates.template_id')
                ->dataSource(
                    WhatsappTemplate::query()
                        ->select([
                            'template_id',
                            'template_name',
                        ])
                        ->get()
                        ->toArray()
                )
                ->optionValue('template_id')
                ->optionLabel('template_name'),

            Filter::select('rel_type', 'campaigns.rel_type')
                ->dataSource(collect(WhatsAppTemplateRelationType::getRelationtype())
                    ->map(fn ($value, $key) => ['value' => $key, 'label' => ucfirst($value)])
                    ->values()
                    ->toArray())
                ->optionValue('value')
                ->optionLabel('label'),

            Filter::datepicker('created_at', 'campaigns.created_at'),

        ];
    }
}