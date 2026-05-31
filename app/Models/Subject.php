<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'name', 'color'])]
class Subject extends Model
{
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    public function generations()
    {
        return $this->hasMany(Generation::class);
    }
}