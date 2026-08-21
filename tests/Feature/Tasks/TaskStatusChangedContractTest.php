<?php

namespace Tests\Feature\Tasks;

use App\Events\TaskStatusChanged;
use DateTimeImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use JsonException;
use Tests\TestCase;

class TaskStatusChangedContractTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_the_broadcast_matches_the_generated_asyncapi_contract(): void
    {
        $contractPath = realpath(base_path('contracts/realtime/generated/task-status-changed.contract.json'))
            ?: realpath(base_path('../../contracts/realtime/generated/task-status-changed.contract.json'));

        $this->assertNotFalse($contractPath, 'Generate realtime contract artifacts before running PHP tests.');

        /** @var array{event: string, channel: string, payload: array{additionalProperties: bool, required: list<string>, properties: array<string, array<string, mixed>>}} $contract */
        $contract = json_decode((string) file_get_contents($contractPath), true, flags: JSON_THROW_ON_ERROR);
        $event = new TaskStatusChanged(
            taskId: '00000000-0000-7000-8000-000000000001',
            state: 'processing',
            version: 2,
            occurredAt: '2026-08-21T12:00:00Z',
            correlationId: '00000000-0000-7000-8000-000000000002',
        );

        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
        $this->assertSame($contract['event'], $event->broadcastAs());

        $channel = $event->broadcastOn();
        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame(
            str_replace('{taskId}', $event->taskId, $contract['channel']),
            $channel->name,
        );

        $payload = $event->broadcastWith();
        $this->assertFalse($contract['payload']['additionalProperties']);
        $this->assertEqualsCanonicalizing($contract['payload']['required'], array_keys($payload));

        foreach ($contract['payload']['properties'] as $name => $schema) {
            $this->assertArrayHasKey($name, $payload);
            $value = $payload[$name];

            match ($schema['type']) {
                'string' => $this->assertIsString($value),
                'integer' => $this->assertIsInt($value),
                default => $this->fail("Unsupported AsyncAPI payload type: {$schema['type']}"),
            };

            if (isset($schema['enum'])) {
                $this->assertContains($value, $schema['enum'], "{$name} must use a contracted value.");
            }

            if (isset($schema['minimum'])) {
                $this->assertGreaterThanOrEqual($schema['minimum'], $value);
            }

            if (($schema['format'] ?? null) === 'date-time') {
                $this->assertInstanceOf(DateTimeImmutable::class, new DateTimeImmutable($value));
            }
        }
    }
}
