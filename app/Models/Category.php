<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name', 'image_id'
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'groups_categories', 'category_id', 'group_id');
    }

    public function image()
    {
        return $this->belongsTo(ImageUpload::class, 'image_id', 'id');
    }
}
