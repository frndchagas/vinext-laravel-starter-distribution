<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdministrativeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DeleteCurrentUserController extends Controller
{
    public function __invoke(Request $request, AdministrativeAccess $administrativeAccess): Response
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $deleted = DB::transaction(function () use ($administrativeAccess, $user): bool {
            $administrativeAccess->lock();

            if ($administrativeAccess->isLastAdmin($user)) {
                return false;
            }

            Auth::guard('web')->logout();
            Auth::forgetGuards();

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
            $user->tokens()->delete();
            $user->delete();

            return true;
        });

        if (! $deleted) {
            return new JsonResponse([
                'type' => 'about:blank',
                'title' => 'Conflict',
                'status' => 409,
                'detail' => 'The Last admin cannot delete their Account.',
                'code' => 'last_admin',
            ], 409, ['Content-Type' => 'application/problem+json']);
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent();
    }
}
