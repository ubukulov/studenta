<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestUserBlock extends Model
{
    use HasFactory;

    protected $table = 'request_user_blocks';

    protected $fillable = [
        'request_user_id', 'block_user_id'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function requestUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id');
    }

    public function blockUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'block_user_id');
    }
}
