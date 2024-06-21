<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    use HasFactory;

    protected $table = 'event_participants';

    protected $fillable = [
        'event_id', 'user_id'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function userSubscribed($event_id, $user_id)
    {
        $record = EventParticipant::where(['event_id' => $event_id, 'user_id' => $user_id])->firstOrFail();
        return ($record) ? true : false;
    }

    public static function subscribe($event_id, $user_id)
    {
        EventParticipant::create([
            'user_id' => $user_id, 'event_id' => $event_id
        ]);
    }
}
