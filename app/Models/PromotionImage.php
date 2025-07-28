<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class PromotionImage extends Model
{
    use HasFactory;

    protected $table = 'promotion_images';

    protected $fillable = [
        'promotion_id', 'image', 'video'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function getImageUrlAttribute($value)
    {
        return $value
            ? Storage::disk('public')->url($value)
            : null;
    }

    public function getVideoUrlAttribute($value)
    {
        return $value
            ? Storage::disk('public')->url($value)
            : null;
    }
}
