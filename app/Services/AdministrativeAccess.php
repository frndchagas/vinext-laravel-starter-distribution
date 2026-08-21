<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

class AdministrativeAccess
{
    public function lock(): void
    {
        Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function isLastAdmin(User $user): bool
    {
        return $user->hasRole('admin') && User::role('admin')->count() <= 1;
    }
}
