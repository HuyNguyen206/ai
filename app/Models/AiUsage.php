<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    public function aiRun()
    {
        return $this->belongsTo(AiRun::class);
    }
}
