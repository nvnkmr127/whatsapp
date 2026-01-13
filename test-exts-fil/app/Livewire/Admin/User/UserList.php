<?php

namespace App\Livewire\Admin\User;

use App\Models\User;
use Livewire\Component;

class UserList extends Component
{
    public User $user;

    public $confirmingDeletion = false;

    public $user_id = null;

    protected $listeners = [
        'editUser'      => 'editUser',
        'viewUser'      => 'viewUser',
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission('user.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
        $this->user = new User;
    }

    public function confirmDelete($userId)
    {
        $this->user_id            = $userId;
        $this->confirmingDeletion = true;
    }

    public function editUser($userId)
    {
        return to_route('admin.users.save', ['userId' => $userId]);
    }

    public function viewUser($userId)
    {
        return to_route('admin.users.details', ['userId' => $userId]);
    }

    public function delete()
    {
        if (checkPermission('user.delete')) {
            User::findOrFail($this->user_id)->delete();
            $this->confirmingDeletion = false;
            $this->notify(['type' => 'success', 'message' => t('user_delete_successfully')]);
            $this->dispatch('pg:eventRefresh-user-table-1la5qd-table');
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-user-table-1la5qd-table');
    }

    public function render()
    {
        return view('livewire.admin.user.user-list');
    }
}
