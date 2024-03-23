<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvatarImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'svg_code',
        'avatar_image_type_id',
        'gender_id',
        'race_id',
        'color_qty',
    ];

    public function avatarImageType()
    {
        return $this->belongsTo(AvatarImageType::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_avatar_images');
    }

}
