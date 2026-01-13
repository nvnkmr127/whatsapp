<?php

namespace App\Livewire\Admin\Table;

use App\Enums\WhatsAppTemplateRelationType;
use App\Models\MessageBots;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class MessageBotTable extends PowerGridComponent
{
    public string $sortField = 'created_at';

    public string $sortDirection = 'DESC';

    public string $tableName = 'message-bot-table-73r5bi-table';

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
        return MessageBots::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name', function ($messageBot) {
                $user = auth()->user();

                $canEdit   = $user->can('message_bot.edit')   || $user->is_admin == 1;
                $canDelete = $user->can('message_bot.delete') || $user->is_admin == 1;
                $canClone  = $user->can('message_bot.clone')  || $user->is_admin == 1;

                return '
            <div class="group relative inline-block min-h-[40px]">
                <span>' . $messageBot->name . '</span>
                <!-- Action Links -->
                <div class="absolute left-[-40px] lg:left-0 top-3 mt-2 pt-1 hidden contact-actions space-x-1 text-xs text-gray-600 dark:text-gray-300">'
                    . ($canEdit ? '<a href="' . route('admin.messagebot.create', ['messagebotId' => $messageBot->id]) . '" class="hover:text-green-600">' . t('edit') . '</a>' : '') .
                    ($canEdit && ($canDelete || $canClone) ? '<span>|</span>' : '') .
                    ($canDelete ? '<button onclick="Livewire.dispatch(\'confirmDelete\', { botId: ' . $messageBot->id . ' })" class="hover:text-red-600">' . t('delete') . '</button>' : '') .
                    ($canDelete && $canClone ? '<span>|</span>' : '') .
                    ($canClone ? '<button onclick="Livewire.dispatch(\'cloneRecord\', { botId: ' . $messageBot->id . ' })" class="hover:text-blue-600">' . t('clone') . '</button>' : '') .
                    '</div>
            </div>';
            })

            ->add(
                'rel_type',
                fn ($msgBot) => $msgBot->rel_type === 'lead'
                    ? '<span class="bg-purple-100 text-purple-800 dark:text-purple-400 dark:bg-purple-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium ">' . t($msgBot->rel_type) . '</span>'
                    : ($msgBot->rel_type === 'customer'
                        ? '<span class="bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20 px-2.5 py-0.5 rounded-full text-xs font-medium ">' . t($msgBot->rel_type) . '</span>'
                        : '<span class="bg-red-100 ring-1 ring-red-300 text-red-800 dark:bg-red-800 dark:ring-red-600 dark:text-red-100 px-3 py-1 rounded-full text-xs font-semibold">' . (t($msgBot->rel_type) ?? 'N/A') . '</span>')
            )
            ->add('trigger', function ($model) {
                $replyTextArray = json_decode($model->trigger);

                return is_array($replyTextArray) ? implode(', ', $replyTextArray) : $model->trigger;
            })
            ->add('reply_type', function ($msgBot) {
                $replyData = WhatsAppTemplateRelationType::getReplyType((int) $msgBot->reply_type);

                return ucfirst($replyData ?? '');
            })

            ->add('created_at_formatted', function ($messageBot) {
                return '<div class="relative group">
                         <span class="cursor-default"  data-tippy-content="' . format_date_time($messageBot->created_at) . '">' . \Carbon\Carbon::parse($messageBot->created_at)->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) . '</span>
                        </div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->searchable()
                ->sortable(),

            Column::make(t('name'), 'name')
                ->searchable()
                ->sortable(),

            Column::make(t('type'), 'reply_type')
                ->searchable()
                ->sortable(),

            Column::make(t('trigger_keyword'), 'trigger')
                ->searchable()
                ->sortable(),

            Column::make(t('relation_type'), 'rel_type')
                ->searchable()
                ->sortable(),

            Column::make(t('active'), 'is_bot_active')
                ->searchable()
                ->sortable()
                ->toggleable(hasPermission: true, trueLabel: 1, falseLabel: 0)
                ->bodyAttribute('flex align-center mt-2'),

            Column::make('Created At', 'created_at_formatted', 'created_at')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            // Relation Type filter
            Filter::select('rel_type')
                ->dataSource(collect(WhatsAppTemplateRelationType::getRelationtype())
                    ->map(fn ($value, $key) => ['value' => $key, 'label' => ucfirst($value)])
                    ->values()
                    ->toArray())
                ->optionValue('value')
                ->optionLabel('label'),

            // Type filter
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

            Filter::datepicker('created_at'),
        ];
    }

    public function onUpdatedToggleable(string $id, string $field, string $value): void
    {
        if (checkPermission('message_bot.edit')) {
            $messageBot = MessageBots::find($id);
            if ($messageBot) {
                $messageBot->is_bot_active = ($value === '1') ? 1 : 0;
                $messageBot->save();

                $statusMessage = $messageBot->is_bot_active
                    ? t('message_bot_is_activated')
                    : t('message_bot_is_deactivated');

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
    public function cloneRecord($botId)
    {
        $existingBot = MessageBots::findOrFail($botId);
        if (! $existingBot) {
            $this->notify(['type' => 'info', 'message' => t('message_bot_not_found')]);

            return false;
        }
        $oldFilePath = $existingBot->filename;
        if ($oldFilePath) {
            $folderPath = 'bot_files';
            $fileName   = pathinfo($oldFilePath, PATHINFO_BASENAME);
            // Ensure the file name follows expected format before splitting
            $fileParts = explode('_', $fileName, 2);
            if (count($fileParts) < 2) {
                $this->notify(['type' => 'danger', 'message' => t('invalid_file_format')]);

                return false;
            }
            $newFileName = time() . '_' . $fileParts[1];
            $newFilePath = $folderPath . '/' . $newFileName;
            if (Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->copy($oldFilePath, $newFilePath);
            } else {
                $this->notify(['type' => 'danger', 'message' => t('original_file_not_found')]);

                return false;
            }
        } else {

            $newFilePath = null;
        }
        $cloneBot           = $existingBot->replicate();
        $cloneBot->filename = $newFilePath;
        $cloneBot->save();

        if ($cloneBot) {
            $this->notify(['type' => 'success', 'message' => t('bot_clone_successfully')], true);

            return redirect(route('admin.messagebot.create', $cloneBot->id));
        }
    }
}
