<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profile';

    protected $fillable = [
        'user_id', 'city_id', 'university_id', 'speciality_id', 'start_year', 'end_year', 'identity_card', 'student_card'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
