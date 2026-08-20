<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Simulated Processing Delay
    |--------------------------------------------------------------------------
    |
    | Extra milliseconds the reference job sleeps before finishing, so state
    | transitions are visible in the interface during local development.
    | Keep it at zero in tests and production.
    |
    */

    'simulated_delay_ms' => (int) env('TASK_SIMULATED_DELAY_MS', 0),

];
