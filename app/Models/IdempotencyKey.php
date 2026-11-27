<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<mixed>|null $errors
 */
class IdempotencyKey extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['errors' => 'array'];
    }
}
