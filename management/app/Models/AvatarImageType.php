<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvatarImageType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'layer_index',
        'display_seq',
        'disabled',
    ];

    public function avatarImages()
    {
        return $this->hasMany(AvatarImage::class);
    }



    public function avatarImageTypeColors()
    {
        return $this->hasMany(AvatarImageTypeColor::class);
    }

}
