<?php

namespace App\Livewire\Admin\Miscellaneous;

use Auth;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class RoleList extends Component
{
    public $confirmingDeletion;

    public $role_id;

    protected $listeners = [
        'editRole'      => 'editRole',
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! Auth::user()->is_admin) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    public function editRole($roleId)
    {
        return to_route('admin.roles.save', ['roleId' => $roleId]);
    }

    public function confirmDelete($roleId)
    {
        $this->role_id            = $roleId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('role.delete')) {
            Role::findOrFail($this->role_id)->delete();
            $this->confirmingDeletion = false;
            $this->notify(['type' => 'success', 'message' => t('role_delete_successfully')]);
            $this->dispatch('pg:eventRefresh-role-table-0ituxt-table');
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-role-table-0ituxt-table');
    }

    public function render()
    {
        return view('livewire.admin.miscellaneous.role-list');
    }
}
