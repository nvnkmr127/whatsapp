<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

#[Layout('layouts.app')]
class ChatRouting extends Component
{
    /**
     * The team instance.
     *
     * @var mixed
     */
    public $team;

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
     * @return void
     */
    public function mount()
    {
        $this->team = Auth::user()->currentTeam;

        // Ensure user has permission to manage settings
        if (!Gate::check('manage-settings')) {
            abort(403);
        }

        $this->statusRules = $this->team->chat_status_rules ?? [];

        $config = $this->team->chat_assignment_config ?? [];
        $this->stickyEnabled = $config['sticky_enabled'] ?? false;
        $this->customRules = $config['rules'] ?? [];
    }

    /**
     * Simulation Properties
     */
    public $simulationPhone = '';
    public $simulationSource = '';
    public $simulationTags = '';
    public $simulationResult = null;
    public $isSimulateModalOpen = false;

    /**
     * Custom Assignment Rules Properties
     */
    public $customRules = [];
    public $stickyEnabled = false;

    /**
     * Rules to validate the status rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'statusRules.*.status_in' => ['required', 'string', 'in:' . implode(',', $this->availableStatuses)],
            'statusRules.*.after_days' => ['required', 'integer', 'min:1', 'max:365'],
            'statusRules.*.status_to' => ['required', 'string', 'in:' . implode(',', $this->availableStatuses)],

            // Custom Rules Validation
            'stickyEnabled' => ['boolean'],
            'customRules.*.priority' => ['required', 'integer'],
            'customRules.*.conditions' => ['array'],
            'customRules.*.assign_to.type' => ['required', 'string', 'in:user,role'],
            // specific validation depends on type, keep it simple for now
        ];
    }

    // ... mount ...


    public function openSimulateModal()
    {
        $this->simulationResult = null;
        $this->simulationPhone = '';
        $this->simulationSource = 'whatsapp';
        $this->simulationTags = '';
        $this->isSimulateModalOpen = true;
    }

    public function runSimulation(\App\Services\AssignmentService $engine)
    {
        $mockContact = new \App\Models\Contact([
            'team_id' => $this->team->id,
            'phone' => $this->simulationPhone,
            'source' => $this->simulationSource,
        ]);

        // Mock Tags
        if (!empty($this->simulationTags)) {
            // Treat comma-separated string as tags
            $tagNames = is_array($this->simulationTags)
                ? $this->simulationTags
                : array_map('trim', explode(',', (string) $this->simulationTags));

            $tags = collect();
            foreach ($tagNames as $name) {
                if (!empty($name)) {
                    // Create a mock object that behaves like a ContactTag
                    $tags->push(new \App\Models\ContactTag(['name' => $name]));
                }
            }

            $mockContact->setRelation('tags', $tags);
        } else {
            $mockContact->setRelation('tags', collect());
        }

        $this->simulationResult = $engine->simulate($mockContact);
    }

    /**
     * Confirmation state for disabling tickets.
     */
    public $confirmingTicketDisabling = false;
    public $ticketDisablingFor = null;
    public $activeTicketsForDisabling = 0;

    /**
     * Toggle ticket assignment for a member.
     *
     * @param int $userId
     * @return void
     */
    public function toggleTicketAssignment($userId)
    {
        $member = $this->team->users()->where('users.id', $userId)->first();

        if ($member) {
            $isReceiving = (bool) $member->membership->receives_tickets;

            // If we are currently receiving, and want to toggle (disable)
            if ($isReceiving) {
                // Check for active tickets
                $count = \App\Models\Contact::where('team_id', $this->team->id)
                    ->where('assigned_to', $userId)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count();

                if ($count > 0) {
                    $this->ticketDisablingFor = $userId;
                    $this->activeTicketsForDisabling = $count;
                    $this->confirmingTicketDisabling = true;
                    return;
                }
            }

            // Otherwise, toggle immediately
            $this->performToggle($userId, !$isReceiving);
        }
    }

    public function disableTicketAssignment()
    {
        if ($this->ticketDisablingFor) {
            $this->performToggle($this->ticketDisablingFor, false);
            $this->confirmingTicketDisabling = false;
            $this->ticketDisablingFor = null;
            $this->activeTicketsForDisabling = 0;
        }
    }

    protected function performToggle($userId, $newState)
    {
        $this->team->users()->updateExistingPivot($userId, [
            'receives_tickets' => $newState,
        ]);

        // Refresh team relation to update UI
        $this->team = $this->team->fresh();

        // Dispatch success to show notification
        $this->dispatch('saved');
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
     * @param int $index
     * @return void
     */
    public function removeStatusRule($index)
    {
        unset($this->statusRules[$index]);
        $this->statusRules = array_values($this->statusRules);
    }

    public function addCustomRule()
    {
        $this->customRules[] = [
            'priority' => count($this->customRules) + 1,
            'conditions' => [
                ['type' => 'tag', 'value' => '']
            ],
            'assign_to' => ['type' => 'role', 'role' => 'agent']
        ];
    }

    public function removeCustomRule($index)
    {
        unset($this->customRules[$index]);
        $this->customRules = array_values($this->customRules);
    }

    public function addCondition($ruleIndex)
    {
        $this->customRules[$ruleIndex]['conditions'][] = ['type' => 'tag', 'value' => ''];
    }

    public function removeCondition($ruleIndex, $conditionIndex)
    {
        unset($this->customRules[$ruleIndex]['conditions'][$conditionIndex]);
        $this->customRules[$ruleIndex]['conditions'] = array_values($this->customRules[$ruleIndex]['conditions']);
    }

    public function saveAssignmentConfig()
    {
        $this->validate([
            'stickyEnabled' => ['boolean'],
            'customRules' => ['array'],
            'customRules.*.priority' => ['required', 'integer'],
            'customRules.*.conditions' => ['array'],
            'customRules.*.assign_to.type' => ['required', 'in:user,role'],
        ]);

        $this->team->forceFill([
            'chat_assignment_config' => [
                'sticky_enabled' => $this->stickyEnabled,
                'rules' => $this->customRules
            ]
        ])->save();

        $this->dispatch('saved');
    }

    /**
     * Save only the status rules.
     *
     * @return void
     */
    public function saveStatusRules()
    {
        $this->validate();

        $this->team->forceFill([
            'chat_status_rules' => $this->statusRules,
        ])->save();

        $this->dispatch('saved');
    }

    /**
     * Get the recommended chat status for a given role.
     *
     * @param string|null $role
     * @return bool
     */
    public function getRecommendedStatus($role)
    {
        return $role === 'agent';
    }

    /**
     * Get the eligibility description for a given role.
     *
     * @param string|null $role
     * @return string
     */
    public function getRoleEligibilityNote($role)
    {
        return match ($role) {
            'agent' => 'Recommended for chat',
            'admin', 'manager' => 'Optional for chat',
            default => 'Not recommended',
        };
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $users = $this->team->users()
            ->when($this->memberSearch, function ($query) {
                $query->where('name', 'like', '%' . $this->memberSearch . '%')
                    ->orWhere('email', 'like', '%' . $this->memberSearch . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.settings.chat-routing', [
            'users' => $users,
            'teamMembers' => $this->team->users()->orderBy('name')->get()
        ]);
    }
}