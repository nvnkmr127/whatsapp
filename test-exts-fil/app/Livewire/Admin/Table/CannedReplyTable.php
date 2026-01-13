<?php

namespace App\Livewire\Admin\Table;

use App\Models\CannedReply;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CannedReplyTable extends PowerGridComponent
{
    public string $tableName = 'canned-reply-table-qxiqed-table';

    public bool $deferLoading = true;

    public string $loadingComponent = 'components.custom-loading';

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
        return CannedReply::query()
            ->when(! auth()->user()->is_admin, function ($query) {
                $query->where(function ($query) {
                    $query->where('is_public', true)
                        ->orWhere('added_from', auth()->id());
                });
            });
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields();
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(t('title'), 'title')
                ->sortable()
                ->searchable(),

            Column::make(t('description'), 'description')
                ->sortable()
                ->searchable(),

            Column::make(t('public'), 'is_public')
                ->sortable()
                ->toggleable(hasPermission: true, trueLabel: t('public'))
                ->searchable()
                ->bodyAttribute('flex align-center mt-2'),

            Column::action(t('action'))
                ->bodyAttribute('wire:key', 'action_{{ $id }}')
                ->hidden(! checkPermission(['canned_reply.edit', 'canned_reply.delete'])),

        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(CannedReply $canned)
    {
        $user    = auth()->user();
        $actions = [];
        if ($user->can('canned_reply.edit') || $user->is_admin == 1) {
            $actions[] = Button::add('edit')
                ->slot(t('edit'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->dispatch('editCannedPage', ['cannedId' => $canned->id]);
        }
        if ($user->can('canned_reply.delete') || $user->is_admin == 1) {
            $actions[] = Button::add('delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch('confirmDelete', ['cannedId' => $canned->id]);
        }

        return $actions ?? [];
    }

    public function actionRules($row): array
    {
        $user = auth()->user();

        return [
            Rule::checkbox()
                ->when(
                    fn ($canned) => $canned->added_from !== $user->id && ! $user->is_admin == true
                )
                ->hide(),

            Rule::rows()
                ->when(fn ($canned) => $canned->added_from !== $user->id && ! $user->is_admin == true)
                ->hideToggleable(),
        ];
    }

    public function onUpdatedToggleable(string $id, string $field, string $value): void
    {
        if (checkPermission('canned_reply.edit')) {
            $cannedReply = CannedReply::find($id);
            if ($cannedReply) {
                $cannedReply->is_public = ($value === '1') ? 1 : 0;
                $cannedReply->save();

                $statusMessage = $cannedReply->is_public
                    ? t('canned_reply_activate')
                    : t('canned_reply_deactivate');

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
}
