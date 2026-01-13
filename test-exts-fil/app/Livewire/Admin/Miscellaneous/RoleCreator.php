<?php

namespace App\Livewire\Admin\Miscellaneous;

use App\Rules\PurifiedInput;
use Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleCreator extends Component
{
    use WithPagination;

    public Role $role;

    public $selectedPermissions = [];

    public $role_id;

    public $assigne_from_contact;

    public function mount($roleId = null)
    {
        if (! Auth::user()->is_admin) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }

        $this->role = ($roleId) ? Role::findOrFail($roleId) : new Role;
        if ($roleId) {
            $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
        }
    }

    protected function rules()
    {
        return [
            'role.name' => [
                'required',
                'unique:roles,name,' . ($this->role->id ?? 'NULL'),
                new PurifiedInput(t('sql_injection_error')),
                'max:255',
            ],
        ];
    }

    public function save()
    {
        if (checkPermission(['role.create', 'role.edit'])) {
            $this->validate();

            try {
                $this->role->save();
                $this->role->syncPermissions($this->selectedPermissions);

                $this->notify(['type' => 'success', 'message' => t('role_save_successfully')], true);

                return redirect()->intended(route('admin.roles.list', absolute: false));
            } catch (\Exception $e) {
                app_log('Failed to save role: ' . $e->getMessage(), 'error', $e, [
                    'role_id'             => $this->role->id ?? null,
                    'selectedPermissions' => $this->selectedPermissions,
                ]);

                $this->notify(['type' => 'danger', 'message' => t('role_save_failed')]);
            }
        }
    }

    public function getPermissionProperty()
    {
        return Permission::all();
    }

    public function render()
    {
        return view('livewire.admin.miscellaneous.role-creator');
    }
}
