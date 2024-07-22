<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmationCode extends Model
{
    use HasFactory;

    protected $table = 'confirmation_codes';

    protected $fillable = [
        'email', 'password', 'code', 'status'
    ];

    protected $dates = [
        'created_at', 'updated_at'
    ];

    public static function get($email, $code): bool
    {
        $confirmation_code = ConfirmationCode::where(['email' => $email, 'code' => $code])->first();
        return (bool) $confirmation_code;
    }

    public static function confirm($email, $code)
    {
        return ConfirmationCode::where(['email' => $email, 'code' => $code])
            ->update(['status' => 'confirmed'])
            ->first();
    }
}
