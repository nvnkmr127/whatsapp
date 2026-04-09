<?php

namespace App\Core\Workflow;

use Illuminate\Database\Eloquent\Model;

interface ActionHandlerInterface
{
    /**
     * Execute the action.
     *
     * @param  array  $config  Action configuration
     * @param  Model  $subject  The model the workflow is running on
     * @param  array  $context  Additional event context
     */
    public function handle(array $config, Model $subject, array $context): void;
}
