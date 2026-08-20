<?php

return [
    'api_host' => env('API_HOST', '127.0.0.1'),
    'api_port' => (int) env('API_PORT', 18000),
    'reverb_port' => (int) env('REVERB_SERVER_PORT', 19080),
];
