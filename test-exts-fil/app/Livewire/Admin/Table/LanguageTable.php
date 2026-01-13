<?php

namespace App\Livewire\Admin\Table;

use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class LanguageTable extends PowerGridComponent
{
    public string $tableName = 'language-table-eoksxv-table';

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
        return Language::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('code');
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
            Column::make(t('code'), 'code')
                ->sortable()
                ->searchable(),
            Column::action(t('action')),
        ];
    }

    public function actions(Language $language): array
    {
        if ($language->code === 'en' || strcasecmp($language->name, 'English') === 0) {
            return [];
        }

        return [
            Button::add('translate')
                ->slot(t('translate'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-green-600 rounded shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600')
                ->route('admin.languages.translations', ['code' => $language->code]),
            Button::add('edit')
                ->slot(t('edit'))
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 justify-center')
                ->dispatch('editLanguage', ['languageCode' => $language->code]),
            Button::add('delete')
                ->slot(t('delete'))
                ->id()
                ->class('inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-white bg-red-600 rounded shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 justify-center')
                ->dispatch('confirmDelete', ['languageId' => $language->id]),
        ];
    }
}
