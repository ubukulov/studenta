<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'name', 'description', 'instagram', 'whatsapp', 'telegram'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(GroupImage::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'group_categories', 'group_id', 'category_id');
    }

    public function subscribes()
    {
        return $this->hasMany(GroupParticipant::class);
    }

    public static function isSubscribe($user_id, $group_id)
    {
        $group_participant = GroupParticipant::where(['user_id' => $user_id, 'group_id' => $group_id])->first();
        return ($group_participant) ? $group_participant : false;
    }

    public static function subscribe($user_id, $group_id)
    {
        GroupParticipant::create([
            'user_id' => $user_id, 'group_id' => $group_id
        ]);
    }

    public static function unSubscribe($group_participant)
    {
        $group_participant->delete();
    }
}
