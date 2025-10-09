<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupReviewReport extends Model
{
    use HasFactory;

    protected $table = 'group_review_reports';
    protected $fillable = [
        'user_id', 'group_review_id', 'type', 'status', 'comment'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GroupReview::class, 'group_review_id');
    }
}
