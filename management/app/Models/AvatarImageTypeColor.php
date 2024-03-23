<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvatarImageTypeColor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'avatar_image_type_id',
        'css_color',
    ];

    public function avatarImageType()
    {
        return $this->belongsTo(AvatarImageType::class);
    }

}
