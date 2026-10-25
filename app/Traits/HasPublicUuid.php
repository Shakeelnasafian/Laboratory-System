<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Puts a random "uuid" column in URLs instead of the auto-increment id, so
 * record numbers cannot be guessed or enumerated. The integer id stays the
 * primary key for relations; the uuid is filled automatically on create.
 */
trait HasPublicUuid
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
