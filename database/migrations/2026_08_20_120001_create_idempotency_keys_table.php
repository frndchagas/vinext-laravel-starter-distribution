<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('operation', 100);
            $table->string('key', 255);
            $table->string('payload_hash', 64);
            $table->uuid('resource_id');
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamps();

            $table->unique(['user_id', 'operation', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
