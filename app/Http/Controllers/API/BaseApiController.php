<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;

class BaseApiController extends Controller
{
    protected $user = null;
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        if(is_null($this->user)) {
            $user = auth('sanctum')->user();
            if($user === null) abort(401);
            $this->user = $user;
        }
        $this->firebase = $firebase;
    }
}
