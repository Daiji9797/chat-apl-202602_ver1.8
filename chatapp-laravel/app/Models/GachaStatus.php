<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GachaStatus extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'gacha_id',
        'stage',
    ];

    protected $casts = [
        'stage' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
