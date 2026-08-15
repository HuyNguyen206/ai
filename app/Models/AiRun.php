<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function aiUsage()
    {
        return $this->hasOne(AiUsage::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
