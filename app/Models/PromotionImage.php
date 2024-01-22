<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionImage extends Model
{
    use HasFactory;

    protected $table = 'promotion_images';

    protected $fillable = [
        'promotion_id', 'image'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
