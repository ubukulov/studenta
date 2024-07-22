<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageUpload extends Model
{
    use HasFactory;

    protected $table = 'image_uploads';

    protected $fillable = [
        'user_id', 'image'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function get($user_id, $image_id): bool
    {
        return (bool) ImageUpload::where(['user_id' => $user_id, 'id' => $image_id])->first();
    }
}
