<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use App\Models\City;
use App\Models\Interest;
use App\Models\Organization;
use App\Models\Speciality;
use App\Models\University;
use App\Models\User;
use Illuminate\Http\Request;
use Auth;
use Validator;

class ApiController extends BaseApiController
{
    /**
     * Login The User
     * @param Request $request
     * @return User
     */
    public function authentication(Request $request)
    {
        try {
            if(!Auth::attempt($request->only(['email', 'password']))){
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            $user->tokens()->delete();

            $token = $user->createToken('API TOKEN')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $token
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $token = $user->createToken('API TOKEN')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function cities()
    {
        return response()->json(City::all());
    }

    public function universities()
    {
        return response()->json(University::all());
    }

    public function specialities()
    {
        return response()->json(Speciality::all());
    }

    public function interests()
    {
        return response()->json(Interest::all());
    }

    public function organizations()
    {
        return response()->json(Organization::all());
    }

    public function categories()
    {
        return response()->json(Category::all());
    }

    public function privacyPolicy()
    {
        $pp = "Privacy Policy text from backend";
        return response()->json($pp);
    }
}
