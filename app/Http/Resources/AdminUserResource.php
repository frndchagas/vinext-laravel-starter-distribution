<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $createdAt = $this->created_at;

        if ($createdAt === null) {
            throw new LogicException('Persisted Users must have a creation timestamp.');
        }

        return [
            'id' => (string) $this->getKey(),
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->hasVerifiedEmail(),
            'roles' => $this->getRoleNames()->values()->all(),
            'created_at' => $createdAt->toISOString(),
        ];
    }
}
