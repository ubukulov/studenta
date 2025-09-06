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
        $this->firebase = $firebase;

        $this->middleware(function ($request, $next) {
            $this->user = auth('sanctum')->user();

            if(!$this->user){
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            return $next($request);
        });
    }
}
