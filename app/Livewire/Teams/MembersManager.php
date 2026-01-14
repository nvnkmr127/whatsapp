<?php

namespace App\Livewire\Teams;

use App\Actions\Custom\CreateUserAndAddToTeam;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;

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
     * Chat Status Rules
     *
     * @var array
     */
    public $statusRules = [];

    /**
     * Search query for members assignment rule
     *
     * @var string
     */
    public $memberSearch = '';

    /**
     * Available statuses for rules
     *
     * @var array
     */
    public $availableStatuses = ['open', 'pending', 'resolved', 'closed'];

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

        $this->statusRules = $this->team->chat_status_rules ?? [];

        parent::mount($this->team);
    }

    /**
     * Toggle ticket assignment for a member.
     *
     * @param  int  $userId
     * @return void
     */
    public function toggleTicketAssignment($userId)
    {
        $member = $this->team->users()->where('users.id', $userId)->first();

        if ($member) {
            $current = (bool) $member->pivot->receives_tickets;
            $this->team->users()->updateExistingPivot($userId, [
                'receives_tickets' => !$current,
            ]);

            $this->team = $this->team->fresh();
            $this->dispatch('saved');
        }
    }

    /**
     * Add a empty status rule.
     *
     * @return void
     */
    public function addStatusRule()
    {
        $this->statusRules[] = [
            'status_in' => 'open',
            'after_days' => 1,
            'status_to' => 'closed',
        ];
    }

    /**
     * Remove a status rule.
     *
     * @param  int  $index
     * @return void
     */
    public function removeStatusRule($index)
    {
        unset($this->statusRules[$index]);
        $this->statusRules = array_values($this->statusRules);
    }

    /**
     * Save only the status rules.
     *
     * @return void
     */
    public function saveStatusRules()
    {
        $this->team->forceFill([
            'chat_status_rules' => $this->statusRules,
        ])->save();

        $this->dispatch('saved');
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
        return view('teams.members')->layout('layouts.app');
    }
}
