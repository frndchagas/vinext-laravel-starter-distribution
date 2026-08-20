<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $operation
 * @property string $key
 * @property string $payload_hash
 * @property string $resource_id
 * @property int $response_status
 * @property array<string, mixed> $response_body
 */
#[Fillable(['user_id', 'operation', 'key', 'payload_hash', 'resource_id', 'response_status', 'response_body'])]
class IdempotencyKey extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_body' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
