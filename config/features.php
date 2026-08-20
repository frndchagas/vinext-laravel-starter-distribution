<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Feature Flags
    |--------------------------------------------------------------------------
    |
    | Flags that let a deployment turn optional flows on or off without code
    | changes. Keep every flag boolean and default it to the behavior the
    | starter template ships with.
    |
    */

    'registration' => (bool) env('FEATURE_REGISTRATION', true),

];
