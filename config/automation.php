<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automation Flow Safeguards
    |--------------------------------------------------------------------------
    | Maximum number of node executions allowed in a single flow run
    | before the system terminates it (to prevent infinite loops).
    |
    */
    'max_steps' => env('AUTOMATION_MAX_STEPS', 50),

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Interruption
    |--------------------------------------------------------------------------
    */
    // If a run is 'executing' but hasn't updated in this long, it's considered hung.
    'crash_recovery_minutes' => env('AUTOMATION_CRASH_RECOVERY_MINUTES', 10),

    // Precision for dispatching delayed jobs (vs CRON pickup)
    'dispatch_precision_seconds' => env('AUTOMATION_DISPATCH_PRECISION', 900),
];
