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

    /*
    |--------------------------------------------------------------------------
    | Delivery Reconciliation
    |--------------------------------------------------------------------------
    |
    | Redis delivery is not atomic with the PostgreSQL transaction that
    | creates a Task. The scheduler retries queued work after a short grace
    | period and resumes processing claims that outlive the complete retry
    | window of the reference job.
    |
    */

    'reconcile_queued_after_seconds' => (int) env('TASK_RECONCILE_QUEUED_AFTER_SECONDS', 60),

    'reconcile_processing_after_seconds' => (int) env('TASK_RECONCILE_PROCESSING_AFTER_SECONDS', 600),

    'reconcile_batch_size' => (int) env('TASK_RECONCILE_BATCH_SIZE', 100),

];
