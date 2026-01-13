<?php

namespace App\Livewire\Admin\Table;

use App\Models\WmActivityLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class WmActivityTable extends PowerGridComponent
{
    public string $sortField = 'wm_activity_logs.created_at';

    public string $sortDirection = 'DESC';

    public string $tableName = 'wm-activity-table-w3tm41-table';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.custom-loading';

    public function setUp(): array
    {
        $this->showCheckBox();

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
        return WmActivityLog::query()
            ->leftJoin('template_bots', function ($join) {
                $join->on('wm_activity_logs.category_id', '=', 'template_bots.id')
                    ->where('wm_activity_logs.category', '=', 'template_bot');
            })
            ->leftJoin('message_bots', function ($join) {
                $join->on('wm_activity_logs.category_id', '=', 'message_bots.id')
                    ->where('wm_activity_logs.category', '=', 'message_bot');
            })
            ->leftJoin('campaigns', function ($join) {
                $join->on('wm_activity_logs.category_id', '=', 'campaigns.id')
                    ->where('wm_activity_logs.category', '=', 'campaign');
            })
            ->leftJoin('whatsapp_templates', 'template_bots.template_id', '=', 'whatsapp_templates.template_id')
            ->select(
                'wm_activity_logs.*',
                DB::raw("COALESCE(template_bots.name, message_bots.name, campaigns.name, '-') as name"),
                DB::raw("
                COALESCE(
                    CASE
                        WHEN wm_activity_logs.category = 'template_bot'
                            AND wm_activity_logs.category_id = template_bots.id
                            THEN (SELECT template_name FROM whatsapp_templates WHERE whatsapp_templates.template_id = template_bots.template_id LIMIT 1)
                        WHEN wm_activity_logs.category = 'campaign'
                            AND wm_activity_logs.category_id = campaigns.id
                            THEN (SELECT template_name FROM whatsapp_templates WHERE whatsapp_templates.template_id = campaigns.template_id LIMIT 1)
                        ELSE '-'
                    END, '-') as template_name
                ")
            );
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('category', fn ($model) => t($model->category))
            ->add(
                'response_code',
                fn ($model) => '<div class="flex justify-center">' . (
                    $model->response_code === '200'
                    ? '<span class="bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">' . $model->response_code . '</span>'
                    : (
                        $model->response_code === '400'
                        ? '<span class="bg-red-100 text-red-800 dark:text-red-400 dark:bg-red-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">' . $model->response_code . '</span>'
                        : '<span class="bg-yellow-100 text-yellow-800 dark:text-yellow-400 dark:bg-yellow-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium">' . ($model->response_code ?? 'N/A') . '</span>'
                    )
                ) . '</div>'
            )
            ->add('rel_type', function ($model) {
                $class = $model->rel_type == 'lead'
                    ? 'bg-indigo-100 text-indigo-800 dark:text-indigo-400 dark:bg-indigo-900/20'
                    : 'bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20';

                return '<div class="flex justify-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . ucfirst($model->rel_type) . '</span></div>';
            })

            ->add('created_at_formatted', function ($model) {
                return '<div class="relative group">
                        <span class="cursor-default" data-tippy-content="' . format_date_time($model->created_at) . '">'
                    . Carbon::parse($model->created_at)->setTimezone(config('app.timezone'))->diffForHumans(['options' => Carbon::JUST_NOW]) . '</span>
                    </div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->sortable()
                ->searchable(),
            Column::make(t('category'), 'category')
                ->sortable()
                ->searchable(),
            Column::make(t('name'), 'name')
                ->sortable(),
            Column::make(t('email_template_name'), 'template_name')
                ->sortable()
                ->searchable(),
            Column::make(t('response_code'), 'response_code')
                ->sortable()
                ->searchable(),
            Column::make(t('relation_type'), 'rel_type')
                ->sortable()
                ->searchable(),
            Column::make(t('created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),
            Column::action(t('action'))
                ->hidden(! checkPermission(['activity_log.view', 'activity_log.delete'])),
        ];
    }

    public function actions(WmActivityLog $row): array
    {
        $user = auth()->user();
        if ($user->can('activity_log.view') || $user->is_admin == 1) {
            $actions[] = Button::add('View')
                ->slot(t('view'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->route('admin.activity-log.details', ['logId' => $row->id]);
        }

        if (checkPermission('activity_log.delete')) {
            $actions[] = Button::add('Delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch('confirmDelete', ['logId' => $row->id]);
        }

        return empty($actions) ? ['-'] : $actions;
    }

    public function header(): array
    {
        $buttons = [];

        if (checkPermission('activity_log.delete')) {
            $buttons[] = Button::add('bulk-delete')
                ->id()
                ->slot(t('bulk_delete') . '(<span x-text="window.pgBulkActions.count(\'' . $this->tableName . '\')"></span>)')
                ->class('inline-flex items-center justify-center px-3 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-red-600 text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 absolute md:top-0 top-[116px] left-[100px] lg:left-[255px] sm:left-[270px] sm:top-0 whitespace-nowrap')
                ->dispatch('bulkDelete.' . $this->tableName, []);
        }

        return $buttons;
    }

    #[On('bulkDelete.{tableName}')]
    public function bulkDelete(): void
    {
        $selectedIds = $this->checkboxValues;
        if (! empty($selectedIds) && count($selectedIds) !== 0) {
            $this->dispatch('confirmDelete', $selectedIds);
            $this->checkboxValues = [];
        } else {
            $this->notify(['type' => 'danger', 'message' => t('no_contact_selected')]);
        }
    }
}
