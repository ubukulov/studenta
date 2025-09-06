<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupParticipant extends Model
{
    use HasFactory;

    protected $table = 'group_participants';

    protected $fillable = [
        'group_id', 'user_id'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function userSubscribed($group_id, $user_id): bool
    {
        $group = Group::find($group_id);
        $record = GroupParticipant::where(['group_id' => $group_id, 'user_id' => $user_id])->first();
        if($group->type == 'admin' && !$record) {
            Group::subscribe($user_id, $group_id);
            return true;
        }

        return (bool) $record;
    }
}
