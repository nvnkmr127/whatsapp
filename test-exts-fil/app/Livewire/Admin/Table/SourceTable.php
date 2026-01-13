<?php

namespace App\Livewire\Admin\Table;

use App\Models\Source;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class SourceTable extends PowerGridComponent
{
    public string $tableName = 'source-table-9hsleg-table';

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
        return Source::query();
    }

    public function relationSearch(): array
    {
        return [];
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

            Column::action(t('action'))
                ->hidden(! checkPermission(['source.edit', 'source.delete'])),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(Source $source)
    {
        $user    = auth()->user();
        $actions = [];

        if ($user->can('source.edit') || $user->is_admin == 1) {
            $actions[] = Button::add('edit')
                ->slot(t('edit'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->dispatch('editSource', ['sourceId' => $source->id]);
        }

        $isSourceUsed = $source->contacts()->exists();

        if ($user->can('source.delete') || $user->is_admin == 1) {
            $actions[] = Button::add('delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch(
                    $isSourceUsed ? 'notify' : 'confirmDelete',
                    $isSourceUsed
                        ? ['message' => t('source_in_use_notify'), 'type' => 'warning']
                        : ['sourceId' => $source->id]
                );
        }

        return $actions == null ? '-' : $actions;
    }
}
