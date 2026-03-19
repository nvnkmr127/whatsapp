<?php

namespace App\Core\Automations;

use App\Models\Contact;
use App\Models\AutomationRun;

interface NodeHandlerInterface
{
    /**
     * Handle the execution of a specific node type.
     * Returns an array with ['status' => 'completed|waiting_input', 'next_node_id' => '...']
     */
    public function handle(Contact $contact, AutomationRun $run, array $nodeData): array;
}
