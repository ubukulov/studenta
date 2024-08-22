<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'group_id', 'name', 'description', 'address', 'two_gis', 'start_date', 'end_date',
        'type', 'cost', 'count_place'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function images()
    {
        return $this->hasMany(EventImage::class);
    }

    public function subscribes()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function getSubscribesCount()
    {
        return count($this->subscribes);
    }

    public static function unSubscribe($event_id, $user_id)
    {
        $event_participant = EventParticipant::where(['event_id' => $event_id, 'user_id' => $user_id])->first();
        $event_participant->delete();
    }
}
