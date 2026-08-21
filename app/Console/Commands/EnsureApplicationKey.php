<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureApplicationKey extends Command
{
    protected $signature = 'app:ensure-key';

    protected $description = 'Generate an application key only when none is configured';

    public function handle(): int
    {
        if (filled(config('app.key'))) {
            $this->components->info('Application key is already configured.');

            return self::SUCCESS;
        }

        return $this->call('key:generate', ['--ansi' => true]);
    }
}
