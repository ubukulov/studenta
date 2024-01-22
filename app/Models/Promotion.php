<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'organization_id', 'establishments_name', 'size', 'address', 'description', 'seats', 'location',
        'start_date', 'end_date'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function images()
    {
        return $this->hasMany(PromotionImage::class, 'promotion_id');
    }
}
