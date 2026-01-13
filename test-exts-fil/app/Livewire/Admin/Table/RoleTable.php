<?php

namespace App\Livewire\Admin\Table;

use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use Spatie\Permission\Models\Role;

final class RoleTable extends PowerGridComponent
{
    public string $sortField = 'created_at';

    public string $sortDirection = 'DESC';

    public string $tableName = 'role-table-0ituxt-table';

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
        return Role::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name', function ($row) {
                return e($row->name);
            })
            ->add('created_at_formatted', function ($row) {
                return '<div class="relative group">
                         <span class="cursor-default"  data-tippy-content="' . format_date_time($row->created_at) . '">' . \Carbon\Carbon::parse($row->created_at)->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) . '</span>
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

            Column::make(t('created_at'), 'created_at_formatted', 'created_at')
                ->sortable()
                ->searchable(),

            Column::action(t('action'))
                ->hidden(! checkPermission(['role.edit', 'role.delete'])),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(Role $role): array
    {
        $actions = [];

        if (checkPermission('role.edit')) {
            $actions[] = Button::add('edit')
                ->slot(t('edit'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->dispatch('editRole', ['roleId' => $role->id]);
        }

        $isUserAssigned = $role->users()->exists();

        if (checkPermission('role.delete')) {
            $actions[] = Button::add('delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch(
                    $isUserAssigned ? 'notify' : 'confirmDelete',
                    $isUserAssigned
                        ? ['message' => t('role_in_use_notify'), 'type' => 'warning']
                        : ['roleId' => $role->id]
                );
        }

        return $actions ?? [];
    }
}
