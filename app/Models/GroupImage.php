<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupImage extends Model
{
    use HasFactory;

    protected $table = 'group_images';

    protected $fillable = [
        'group_id', 'path'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
