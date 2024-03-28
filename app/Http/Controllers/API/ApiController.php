<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Group;
use App\Models\Interest;
use App\Models\Organization;
use App\Models\Promotion;
use App\Models\PromotionImage;
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

            if(!$user->tokens) {
                $token = $user->createToken('API TOKEN')->plainTextToken;
            } else {
                $token = $user->currentAccessToken();
            }

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

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // 1
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        // 2
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        // 3
        $token = $user->createToken('API TOKEN')->plainTextToken;

        // 4
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

    public function promotions()
    {
        $promotions = Promotion::orderBy('size', 'DESC')
            ->with('category', 'organization')
            ->get();
        return response()->json($promotions);
    }

    public function getPromotionById($id)
    {
//        $promotion = Promotion::findOrFail($id);
        $promotion = Promotion::with('category', 'organization', 'images')
                ->findOrFail($id);
        return response()->json($promotion);
    }

    public function getPromotionImagesById($id)
    {
        $promotion_images = PromotionImage::where(['promotion_id' => $id])->get();
        return response()->json($promotion_images);
    }

    public function events()
    {
        $events = Event::with('user', 'group', 'images')->get();
        return response()->json($events);
    }

    public function getEventById($id)
    {
        $event = Event::with('user', 'group', 'images')->findOrFail($id);
        return response()->json($event);
    }
}
