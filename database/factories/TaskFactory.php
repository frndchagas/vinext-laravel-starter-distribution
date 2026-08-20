<?php

namespace Database\Factories;

use App\Enums\TaskState;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'input' => fake()->sentence(),
            'state' => TaskState::Queued,
            'version' => 1,
            'correlation_id' => (string) Str::uuid7(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'state' => TaskState::Completed,
            'version' => 3,
            'output' => ['word_count' => 3, 'reversed' => 'cba'],
            'finished_at' => now(),
        ]);
    }
}
