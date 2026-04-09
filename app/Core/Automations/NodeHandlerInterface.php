<?php

namespace App\Core\Automations;

use App\Models\AutomationRun;
use App\Models\Contact;

interface NodeHandlerInterface
{
    /**
     * Handle the execution of a specific node type.
     * Returns an array with ['status' => 'completed|waiting_input', 'next_node_id' => '...']
     */
    public function handle(Contact $contact, AutomationRun $run, array $nodeData): array;
}
