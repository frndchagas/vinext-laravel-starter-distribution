<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\AdministrativeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('users.view');

        $perPage = min(50, max(1, (int) $request->query('per_page', '15')));
        $search = mb_strtolower(trim((string) $request->query('search', '')));

        $page = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        return response()->json([
            'data' => AdminUserResource::collection($page->items())->resolve(),
            'meta' => [
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user,
        AdministrativeAccess $administrativeAccess,
    ): AdminUserResource|JsonResponse {
        Gate::authorize('users.manage');

        /** @var User $actor */
        $actor = $request->user();
        $role = (string) $request->validated('role');

        return DB::transaction(function () use ($actor, $administrativeAccess, $role, $user): AdminUserResource|JsonResponse {
            $administrativeAccess->lock();

            if ($actor->is($user)) {
                return $this->conflict(
                    'cannot_change_own_role',
                    'Administrators cannot change their own role.',
                );
            }

            if ($role === 'member' && $administrativeAccess->isLastAdmin($user)) {
                return $this->conflict(
                    'last_admin',
                    'The Last admin cannot be demoted.',
                );
            }

            $user->syncRoles([$role]);

            return new AdminUserResource($user->load('roles'));
        });
    }

    private function conflict(string $code, string $detail): JsonResponse
    {
        return response()->json([
            'type' => 'about:blank',
            'title' => 'Conflict',
            'status' => 409,
            'detail' => $detail,
            'code' => $code,
        ], 409, ['Content-Type' => 'application/problem+json']);
    }
}
