<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'type', 'topic'])]
class Generation extends Model
{
    /**
     * A generation belongs to a user (inverse one-to-many).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
