<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    use HasFactory;

    protected $table = 'universities';

    protected $fillable = [
        'name', 'city_id', 'legal_address', 'email', 'year_of_foundation', 'director', 'contacts'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
