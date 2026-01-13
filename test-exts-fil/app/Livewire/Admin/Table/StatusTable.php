<?php

namespace App\Livewire\Admin\Table;

use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class StatusTable extends PowerGridComponent
{
    public string $tableName = 'status-table-nz8nvq-table';

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
        return Status::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('color', fn ($value) => $value->color === $value->color ? '<span class="inline-flex items-center rounded-full px-2 py-2" style="background-color: ' . e($value->color) . ';"></span>' : 'N/A');
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
            Column::make(t('status_color'), 'color')
                ->sortable()
                ->searchable(),

            Column::action(t('action'))
                ->hidden(! checkPermission(['status.edit', 'status.delete'])),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(Status $status)
    {

        $actions = [];

        if (checkPermission('status.edit')) {
            $actions[] = Button::add('edit')
                ->slot(t('edit'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->dispatch('editStatus', ['statusId' => $status->id]);
        }

        $isStatusUsed = $status->contacts()->exists();

        if (checkPermission('status.delete')) {
            $actions[] = Button::add('delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch(
                    $isStatusUsed ? 'notify' : 'confirmDelete',
                    $isStatusUsed
                        ? ['message' => t('status_delete_in_use_notify'), 'type' => 'warning']
                        : ['statusId' => $status->id]
                );
        }

        return $actions ?? [];
    }
}
