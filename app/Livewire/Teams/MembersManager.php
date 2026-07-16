<?php

namespace App\Livewire\Teams;

use App\Actions\Custom\CreateUserAndAddToTeam;
use App\Models\Contact;
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
     * The active ticket count for the user being managed/removed.
     *
     * @var int
     */
    public $activeTicketCount = 0;

    public function manageRole($userId)
    {
        parent::manageRole($userId);
        $this->calculateActiveTickets($userId);
    }

    public function confirmTeamMemberRemoval($userId)
    {
        parent::confirmTeamMemberRemoval($userId);
        $this->calculateActiveTickets($userId);
    }

    /**
     * Confirm leaving the team — warn about the current user's active tickets first.
     */
    public function confirmLeaving()
    {
        $this->calculateActiveTickets($this->user->id);
        $this->confirmingLeavingTeam = true;
    }

    /**
     * Re-send a pending team invitation email.
     */
    public function resendTeamInvitation($invitationId)
    {
        if (! \Illuminate\Support\Facades\Gate::check('addTeamMember', $this->team)) {
            abort(403);
        }

        $invitation = $this->team->teamInvitations()->findOrFail($invitationId);

        \Illuminate\Support\Facades\Mail::to($invitation->email)
            ->send(new \Laravel\Jetstream\Mail\TeamInvitation($invitation));

        session()->flash('message', __('Invitation resent to :email.', ['email' => $invitation->email]));
    }

    protected function calculateActiveTickets($userId)
    {
        // Contacts assigned to this user that still have an active conversation.
        // (Ticket status lives on Conversation, not Contact.)
        $this->activeTicketCount = Contact::where('team_id', $this->team->id)
            ->where('assigned_to', $userId)
            ->whereHas('activeConversation')
            ->count();
    }

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

        if (! \Illuminate\Support\Facades\Gate::check('view', $this->team)) {
            abort(403);
        }

        parent::mount($this->team);
    }

    public function openAddMemberModal()
    {
        if (! \Illuminate\Support\Facades\Gate::check('addTeamMember', $this->team)) {
            abort(403);
        }

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
     * @return void
     */
    public function createUser(CreateUserAndAddToTeam $creator)
    {
        if (! \Illuminate\Support\Facades\Gate::check('addTeamMember', $this->team)) {
            abort(403);
        }

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
        if (! \Illuminate\Support\Facades\Gate::check('addTeamMember', $this->team)) {
            abort(403);
        }

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
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('teams.members', [
            'users' => $users,
        ]);
    }
}
