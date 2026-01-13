<?php

namespace App\Livewire\Admin\Table;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class UserTable extends PowerGridComponent
{
    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public string $tableName = 'user-table-1la5qd-table';

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
        $user = Auth::User();

        return User::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('firstname', function ($user) {
                $isUserAssigned = $user->contacts()->exists();

                // Determine delete action based on user assignment status
                $deleteAction = $isUserAssigned
                    ? "Livewire.dispatch('notify', { message: '" . t('user_in_use_notify') . "', type: 'warning' })"
                    : "Livewire.dispatch('confirmDelete', { userId: {$user->id} })";

                // Determine profile image
                $profile_img = $user->profile_image_url && Storage::disk('public')->exists($user->profile_image_url)
                    ? asset('storage/' . $user->profile_image_url)
                    : asset('img/user-placeholder.jpg');

                $loggedInUser = Auth::user();

                // Start rendering output
                $output = '<div class="group relative inline-block min-h-[40px]">
                <div class="flex items-center gap-3 w-auto min-w-0 max-w-full ">
                    <img src="' . $profile_img . '" class="inline-block object-cover h-8 w-8 rounded-full">
                    <p class="dark:text-gray-200 text-indigo-600 dark:hover:text-indigo-400 text-sm break-words truncate">' . $user->firstname . ' ' . $user->lastname . '</p>
                </div>

                <!-- Action Links (Hidden by Default, Shown on Hover) -->
                <div class="absolute contact-actions dark:text-gray-300 group-hover:flex hidden left-0 mt-5 space-x-1 text-gray-600 text-xs top-3">';

                if (checkPermission('user.view')) {
                    $output .= ' <button onclick="Livewire.dispatch(\'viewUser\', { userId: ' . $user->id . ' })" class="hover:text-blue-600">' . t('view') . '</button>';
                }
                if (checkPermission('user.edit')) {
                    $output .= ' <span>|</span><button onclick="Livewire.dispatch(\'editUser\', { userId: ' . $user->id . ' })" class="hover:text-green-600">' . t('edit') . '</button>';
                }

                if (checkPermission('user.delete')) {
                    if (
                        auth()->user()->is_admin === 1 && auth()->user()->id !== $user->id || (
                            auth()->user()->is_admin !== 1 && $user->is_admin !== 1 && auth()->user()->id !== $user->id
                        )
                    ) {
                        $output .= '<span>|</span>
                            <button onclick="' . $deleteAction . '" class="hover:text-red-600">' . t('delete') . '</button>';
                    }
                }

                $output .= '</div>
                </div>
            </div>';

                return $output;
            })

            ->add('phone', fn ($user) => $user->phone ?? '-')
            ->add('role_id', fn ($user) => $user->is_admin ? 'Admin' : $user->getRoleNames()->first())
            ->add('created_at_formatted', function ($user) {
                return '<div class="relative group">
                     <span class="cursor-default" data-tippy-content="' . format_date_time($user->created_at) . '">'
                    . \Carbon\Carbon::parse($user->created_at)->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW])
                    . '</span>
                    </div>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make(t('ids'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(t('name'), 'firstname')
                ->bodyAttribute('relative mb-2')
                ->sortable()
                ->searchableRaw("CONCAT(firstname, ' ', lastname) LIKE ?")
                ->searchable(),

            Column::make(t('phone'), 'phone')
                ->sortable()
                ->searchable(),

            Column::make(t('email'), 'email')
                ->sortable()
                ->searchable(),

            Column::make(t('role'), 'role_id')
                ->sortable()
                ->searchable(),

            Column::make(t('status'), 'active')
                ->toggleable(hasPermission: true, trueLabel: 1, falseLabel: 0)
                ->bodyAttribute('flex align-center mt-2')
                ->sortable()
                ->searchable(),

            Column::make(t('created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function onUpdatedToggleable(string $id, string $field, string $value): void
    {
        $user = User::find($id);
        if (checkPermission('user.edit')) {
            if ($user) {
                if (auth()->id() === $user->id) {
                    $this->notify([
                        'message' => t('account_cannot_be_deactivated'),
                        'type'    => 'warning',
                    ]);

                    return;
                }

                if (! auth()->user()->is_admin && $user->is_admin) {
                    $this->notify([
                        'message' => t('account_cannot_be_deactivated'),
                        'type'    => 'warning',
                    ]);

                    return;
                }

                if (auth()->user()->is_admin && $user->is_admin) {
                    $user->active    = ($value === '1') ? 1 : 0;
                    $user->banned_at = ! $user->active ? now() : null;
                    $user->save();

                    $statusMessage = $user->active
                        ? t('user_activated_successfully')
                        : t('user_deactivated_successfully');

                    $this->notify([
                        'message' => $statusMessage,
                        'type'    => 'success',
                    ]);

                    return;
                }

                $user->active    = ($value === '1') ? 1 : 0;
                $user->banned_at = ! $user->active ? now() : null;
                $user->save();

                $statusMessage = $user->active
                    ? t('user_activated_successfully')
                    : t('user_deactivated_successfully');

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
