<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedDocument extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'filename',
        'provider_file_id',
        'provider_store_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array', // ensure JSON becomes an array
    ];

    public function team()
    {
        return $this->belongsTo(Team::class); // ownership by team
    }

    public function user()
    {
        return $this->belongsTo(User::class); // ownership by user
    }
}
