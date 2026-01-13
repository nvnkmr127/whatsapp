<?php

namespace App\Livewire\Admin\Table;

use App\Models\WhatsappTemplate;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class WhatsppTemplateTable extends PowerGridComponent
{
    public string $tableName = 'whatspp-template-table-sgz2iu-table';

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
        return WhatsappTemplate::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add(
                'status',
                fn ($contact) => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' .
                    ($contact->status === 'APPROVED'
                    ? 'bg-green-100 text-green-800 dark:text-green-400 dark:bg-green-900/20'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') .
                    '">' . ($contact->status ?? 'N/A') . '</span>'
            )->add('header_data_format', fn ($contact) => $contact->header_data_format == null ? '-' : $contact->header_data_format);
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->searchable()
                ->sortable(),

            Column::make(t('email_template_name'), 'template_name')
                ->searchable()
                ->sortable(),

            Column::make(t('languages'), 'language')
                ->searchable()
                ->sortable(),

            Column::make(t('category'), 'category')
                ->searchable()
                ->sortable(),

            Column::make(t('template_type'), 'header_data_format')
                ->searchable()
                ->sortable(),

            Column::make(t('status'), 'status')
                ->searchable()
                ->sortable(),

            Column::make(t('body_data'), 'body_data')
                ->searchable()
                ->sortable()
                ->headerAttribute('text-wrap', 'white-space: normal;')
                ->bodyAttribute('text-wrap', 'white-space: normal; word-wrap: break-word;'),

        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert(' . $rowId . ')');
    }

    public function filters(): array
    {
        return [
            Filter::inputText('template_name')->placeholder('Template Name'),

            $this->createSelectFilter('language'),
            $this->createSelectFilter('category'),
            $this->createSelectFilter('header_data_format'),
            $this->createSelectFilter('status'),
        ];
    }

    public function createSelectFilter(string $field)
    {
        return Filter::select($field, $field)
            ->dataSource(
                WhatsappTemplate::select($field)
                    ->distinct()
                    ->pluck($field)
                    ->map(fn ($value) => ['id' => $value, 'name' => $value])
            )
            ->optionLabel('name')
            ->optionValue('id');
    }
}
