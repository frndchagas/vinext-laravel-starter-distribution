<?php

use Illuminate\Foundation\DevCommands;

DevCommands::artisan(
    'serve --host='.config('development.api_host').' --port='.((int) config('development.api_port')),
    'server',
);

// The web app lives in apps/web and runs through the root Bun command; the "vite"
// process the framework registers for this package.json would recurse
// into `artisan dev` itself.
DevCommands::except('vite');
