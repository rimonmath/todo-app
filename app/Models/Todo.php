<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['text', 'is_completed', 'user_id'])]
class Todo extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }   //
}
