<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseApiController extends Controller
{
    protected $user = null;

    public function __construct()
    {
        if(is_null($this->user)) {
            if(auth('sanctum')->user() === null) abort(401);
            $this->user = auth('sanctum')->user();
        }
    }
}
