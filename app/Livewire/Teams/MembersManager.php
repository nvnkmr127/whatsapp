<?php

namespace App\Livewire\Teams;

use App\Actions\Custom\CreateUserAndAddToTeam;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class MembersManager extends TeamMemberManager
{
    /**
     * The "create user" form state.
     *
     * @var array
     */
    public $createUserForm = [
        'name' => '',
        'email' => '',
        'password' => '',
        'role' => null,
    ];

    /**
     * Indicates if the add member modal is open.
     *
     * @var bool
     */
    public $isAddMemberModalOpen = false;

    /**
     * Search query for members
     *
     * @var string
     */
    public $search = '';

    /**
     * Mount the component.
     *
     * @param  mixed|null  $team
     * @return void
     */
    public function mount($team = null)
    {
        $this->team = $team ?: \Illuminate\Support\Facades\Auth::user()->currentTeam;

        if (!\Illuminate\Support\Facades\Gate::check('addTeamMember', $this->team)) {
            abort(403);
        }

        parent::mount($this->team);
    }

    public function openAddMemberModal()
    {
        $this->resetErrorBag();
        $this->isAddMemberModalOpen = true;
    }

    public function closeAddMemberModal()
    {
        $this->isAddMemberModalOpen = false;
        $this->reset('createUserForm', 'addTeamMemberForm');
    }

    /**
     * Create a new user and add them to the team.
     *
     * @param  \App\Actions\Custom\CreateUserAndAddToTeam  $creator
     * @return void
     */
    public function createUser(CreateUserAndAddToTeam $creator)
    {
        $this->resetErrorBag();

        $creator->create(
            $this->user,
            $this->team,
            $this->createUserForm
        );

        $this->createUserForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => null,
        ];

        $this->team = $this->team->fresh();

        $this->dispatch('saved');
        $this->closeAddMemberModal();
    }

    public function addTeamMember()
    {
        parent::addTeamMember();
        $this->closeAddMemberModal();
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $users = $this->team->users()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('teams.members', [
            'users' => $users
        ]);
    }
}
