<?php

namespace App\Livewire\Admin\Table;

use App\Enums\WhatsAppTemplateRelationType;
use App\Models\TemplateBot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TemplateBotTable extends PowerGridComponent
{
    public string $sortField = 'created_at';

    public string $sortDirection = 'DESC';

    public string $tableName = 'template-bot-table-dgvpzs-table';

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
        return TemplateBot::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')

            ->add('name', function ($tempBot) {
                $user = auth()->user();

                $canEdit   = $user->can('template_bot.edit')   || $user->is_admin == 1;
                $canDelete = $user->can('template_bot.delete') || $user->is_admin == 1;
                $canClone  = $user->can('template_bot.clone')  || $user->is_admin == 1;

                return '<div class="group relative inline-block min-h-[40px]">
                 <span>' . $tempBot->name . '</span>
                 <!-- Action Links -->
                <div class="absolute left-[-40px] lg:left-0 top-3 mt-2 pt-1 hidden contact-actions space-x-1 text-xs text-gray-600 dark:text-gray-300">
                  ' . ($canEdit ? '<a href="' . route('admin.templatebot.create', ['templatebotId' => $tempBot->id]) . '" class="hover:text-green-600">' . t('edit') . '</a>' : '') .
                    ($canEdit && ($canDelete || $canClone) ? '<span>|</span>' : '') .
                    ($canDelete ? '<button onclick="Livewire.dispatch(\'confirmDelete\', { templatebotId: ' . $tempBot->id . ' })" class="hover:text-red-600">' . t('delete') . '</button>' : '') . ($canDelete && $canClone ? '<span>|</span>' : '') .
                    ($canClone ? '<button class="hover:text-blue-600" onclick="Livewire.dispatch(\'cloneRecord\', { templatebotId: ' . $tempBot->id . ' })">' . t('clone') . '</button>' : '') . '</div>
            </div>';
            })
            ->add('reply_type', function ($templateBot) {
                return ucfirst(WhatsAppTemplateRelationType::getReplyType($templateBot->reply_type) ?? '');
            })

            ->add('rel_type', function ($templateBot) {
                $class = $templateBot->rel_type == 'lead'
                    ? 'bg-indigo-100 text-indigo-800 dark:text-indigo-400 dark:bg-indigo-900/20'
                    : 'bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20';

                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . t($templateBot->rel_type) . '</span>';
            })
            ->add('created_at_formatted', function ($templateBot) {
                return '<div class="relative group">
                         <span class="cursor-default"  data-tippy-content="' . format_date_time($templateBot->created_at) . '">' . \Carbon\Carbon::parse($templateBot->created_at)->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) . '</span>
                        </div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(t('name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(t('reply_type'), 'reply_type')
                ->sortable()
                ->searchable(),

            Column::make(t('trigger_keyword'), 'trigger')
                ->sortable()
                ->searchable(),

            Column::make(t('relation_type'), 'rel_type')
                ->sortable()
                ->searchable(),

            Column::make(t('active'), 'is_bot_active')
                ->searchable()
                ->sortable()
                ->toggleable(hasPermission: true, trueLabel: 1, falseLabel: 0)
                ->bodyAttribute('flex align-center mt-2'),

            Column::make(t('created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            // Reply Type Filter
            Filter::select('reply_type')
                ->dataSource(collect(WhatsAppTemplateRelationType::getReplyType())
                    ->map(fn ($value, $key) => [
                        'value' => $key,
                        'label' => ucfirst($value['label'] ?? ''),
                    ])
                    ->values()
                    ->toArray())
                ->optionValue('value')
                ->optionLabel('label'),

            // RelationType Filter
            Filter::select('rel_type')
                ->dataSource(collect(WhatsAppTemplateRelationType::getRelationtype())
                    ->map(fn ($value, $key) => ['value' => $key, 'label' => ucfirst($value)])
                    ->values()
                    ->toArray())
                ->optionValue('value')
                ->optionLabel('label'),

            Filter::datepicker('created_at'),
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($templatebotId)
    {
        return redirect(route('admin.templatebot.create', ['templatebotId' => $templatebotId]));
    }

    public function onUpdatedToggleable(string $id, string $field, string $value): void
    {
        if (checkPermission('template_bot.edit')) {
            $templateBot = TemplateBot::find($id);
            if ($templateBot) {
                $templateBot->is_bot_active = ($value === '1') ? 1 : 0;
                $templateBot->save();

                $statusMessage = $templateBot->is_bot_active
                    ? t('template_bot_activate')
                    : t('template_bot_deactivate');

                $this->notify([
                    'message' => $statusMessage,
                    'type'    => 'success',
                ]);
            }
        } else {
            $this->notify([
                'message' => t('no_permission_to_perform_action'),
                'type'    => 'warning',
            ]);
        }
    }

    #[\Livewire\Attributes\On('cloneRecord')]
    public function cloneRecord($templatebotId)
    {
        $existingBot = TemplateBot::findOrFail($templatebotId);
        if (! $existingBot) {
            $this->notify(['type' => 'info', 'message' => t('template_bot_not_found')]);

            return false;
        }

        $oldFilePath = $existingBot->filename;
        $newFilePath = null;

        if ($oldFilePath) {
            $folderPath = 'template_bot_files/clone';
            $fileName   = pathinfo($oldFilePath, PATHINFO_BASENAME);

            $fileParts = explode('_', $fileName);

            // Ensure we have at least three parts before accessing index [2]
            $originalName = isset($fileParts[2]) ? $fileParts[2] : $fileName;
            $newFileName  = time() . '_' . $originalName;
            $newFilePath  = $folderPath . '/' . $newFileName;

            if (Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->copy($oldFilePath, $newFilePath);
            } else {
                $newFilePath = null;
            }
        }

        // Clone the bot and update the filename field
        $cloneBot           = $existingBot->replicate();
        $cloneBot->filename = $newFilePath;
        $cloneBot->save();

        if ($cloneBot) {
            $this->notify(['type' => 'success', 'message' => t('bot_clone_successfully')], true);

            return redirect(route('admin.templatebot.create', $cloneBot->id));
        }
    }
}
