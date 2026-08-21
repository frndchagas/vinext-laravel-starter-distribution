<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ReadinessTest extends TestCase
{
    public function test_readiness_checks_the_database_and_cache(): void
    {
        $this->getJson('/ready')
            ->assertOk()
            ->assertHeader('X-Correlation-Id')
            ->assertExactJson([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                ],
            ]);
    }

    public function test_readiness_returns_service_unavailable_when_the_database_fails(): void
    {
        DB::shouldReceive('selectOne')->once()->andThrow(new RuntimeException('database unavailable'));

        $this->getJson('/ready')
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'unavailable')
            ->assertJsonPath('checks.database', 'unavailable')
            ->assertJsonPath('checks.cache', 'ok');
    }

    public function test_readiness_returns_service_unavailable_when_the_cache_fails(): void
    {
        Cache::shouldReceive('get')->once()->andThrow(new RuntimeException('cache unavailable'));

        $this->getJson('/ready')
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'unavailable')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'unavailable');
    }
}
