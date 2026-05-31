<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'subject_id', 'type', 'topic', 'content'])]
class Generation extends Model
{
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}