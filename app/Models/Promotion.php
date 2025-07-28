<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'organization_id', 'establishments_name', 'size', 'address', 'description', 'seats', 'location',
        'two_gis', 'start_date', 'end_date', 'notified'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function images()
    {
        return $this->hasMany(PromotionImage::class, 'promotion_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
