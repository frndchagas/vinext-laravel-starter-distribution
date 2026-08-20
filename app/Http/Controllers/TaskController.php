<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Services\CreateTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, CreateTask $createTask): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $correlationId = (string) $request->attributes->get('correlation_id');

        $result = $createTask(
            $user,
            (string) $request->validated('input'),
            (string) $request->validated('idempotency_key'),
            $correlationId,
        );

        return response()
            ->json($result->body, $result->status)
            ->header('Location', route('tasks.show', ['task' => $result->taskId]));
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = min(50, max(1, (int) $request->query('per_page', '15')));

        $page = Task::query()
            ->whereBelongsTo($user)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => TaskResource::collection($page->items())->resolve(),
            'meta' => [
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    public function show(Request $request, Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }
}
