<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speciality extends Model
{
    use HasFactory;

    protected $table = 'specialities';

    protected $fillable = [
        'name', 'university_id', 'code'
    ];

    public function university(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
