<?php

namespace App\Livewire\Admin\Table;

use App\Models\AiPrompt;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AiPromptTable extends PowerGridComponent
{
    public string $tableName = 'ai-prompt-table-iywkrn-table';

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
        return AiPrompt::query();
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

            Column::make(t('prompt_action'), 'action')
                ->sortable()
                ->searchable(),

            Column::action(t('action'))
                ->hidden(! checkPermission(['ai_prompt.edit', 'ai_prompt.delete'])),
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields();
    }

    public function actions(AiPrompt $prompt)
    {
        $user    = auth()->user();
        $actions = [];
        if ($user->can('ai_prompt.edit') || $user->is_admin == 1) {
            $actions[] = Button::add('edit')
                ->slot(t('edit'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->dispatch('editAiPrompt', ['promptId' => $prompt->id]);
        }
        if ($user->can('ai_prompt.delete') || $user->is_admin == 1) {
            $actions[] = Button::add('delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch('confirmDelete', ['promptId' => $prompt->id]);
        }

        return empty($actions) ? '-' : $actions;
    }
}
