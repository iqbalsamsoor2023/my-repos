<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Test Execution Guard
    |--------------------------------------------------------------------------
    |
    | Prevent test execution on protected environments. Keep staging blocked
    | by default and temporarily enable with TEST_GUARD_ALLOW_STAGING=true.
    |
    */

    'blocked_environments' => env('TEST_GUARD_BLOCKED_ENVS', 'production,staging'),

    'allow_staging' => env('TEST_GUARD_ALLOW_STAGING', false),

];
