<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GachaImage extends Model
{
    protected $table = 'gacha_images';

    protected $fillable = [
        'gacha_id',
        'stage',
        'filename',
        'image_path',
    ];
}
