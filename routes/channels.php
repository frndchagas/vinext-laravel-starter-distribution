<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', function (User $user, string $id): bool {
    return $user->hasVerifiedEmail() && (string) $user->getKey() === $id;
});

Broadcast::channel('tasks.{task}', function (User $user, Task $task): bool {
    return $user->hasVerifiedEmail() && $task->user_id === $user->getKey();
});
