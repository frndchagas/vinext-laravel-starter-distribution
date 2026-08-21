<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class EnsureApplicationKeyTest extends TestCase
{
    public function test_it_keeps_an_existing_application_key(): void
    {
        config(['app.key' => 'base64:test-existing-key']);
        $key = config('app.key');

        $this->artisan('app:ensure-key')
            ->expectsOutputToContain('already configured')
            ->assertSuccessful();

        $this->assertSame($key, config('app.key'));
    }
}
