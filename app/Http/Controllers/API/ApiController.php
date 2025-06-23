<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\ConfirmationCode;
use App\Models\Event;
use App\Models\Group;
use App\Models\Interest;
use App\Models\Organization;
use App\Models\Promotion;
use App\Models\Speciality;
use App\Models\University;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Esputnik;
use Illuminate\Support\Str;

class ApiController extends Controller
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
                ], 422);
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
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            $input = $request->all();
            $input['code'] = rand(1000,9999);

            $confirmation = ConfirmationCode::create($input);

            $data = [
                'name' => $confirmation->name ?? "Посетитель",
                'code' => $confirmation->code,
                'email' => $input['email'],
            ];

            Esputnik::sendEmail(4054454, $data);

            DB::commit();

            return response()->json('Код подтверждение регистрации отправлено на вашу почту', 200,[],JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function confirmationCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        if(ConfirmationCode::get($data['email'], $data['code'])) {
            ConfirmationCode::confirm($data['email'], $data['code']);
            $confirmation_code = ConfirmationCode::where(['email' => $data['email'], 'code' => $data['code']])->first();
            $user = User::create([
                'name' => $confirmation_code->name ?? null, 'email' => $data['email'], 'password' => bcrypt($confirmation_code->password),
                'device_token' => $data['device_token'] ?? null
            ]);

            $token = $user->createToken('API TOKEN')->plainTextToken;

            return response()->json(['token' => $token], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json('Неверный код подтверждения', 422, [], JSON_UNESCAPED_UNICODE);
    }

    public function cities(): \Illuminate\Http\JsonResponse
    {
        return response()->json(City::all());
    }

    public function universities(): \Illuminate\Http\JsonResponse
    {
        return response()->json(University::all());
    }

    public function specialities(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Speciality::all());
    }

    public function interests(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Interest::all());
    }

    public function organizations(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Organization::all());
    }

    public function categories(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Category::all());
    }

    public function privacyPolicy(): \Illuminate\Http\JsonResponse
    {
        $pp = "Privacy Policy text from backend";
        return response()->json($pp);
    }

    public function forgetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email']);
            $email = $request->get('email');
            $user = User::whereEmail($email)->first();
            if (!$user) {
                return response()->json('Пользователь с таким email не найдено', 404, [], JSON_UNESCAPED_UNICODE);
            }

            $new_password = Str::random(8);
            $user->password = bcrypt($new_password);
            $user->save();

            $data = [
                'name' => $user->name ?? "Посетитель",
                'code' => $new_password,
                'email' => $user->email,
            ];

            Esputnik::sendEmail(4059313, $data);

            return response()->json('Новый пароль успешно отправлено на почту', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function promotions(): \Illuminate\Http\JsonResponse
    {
        $promotions = Promotion::orderBy('size', 'DESC')
            ->with('category', 'organization', 'images')
            ->get();
        return response()->json($promotions);
    }

    public function getEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->whereDate('end_date', '>=', date('Y-m-d'))
            ->get();

        return response()->json($events);
    }

    public function getGroups(): \Illuminate\Http\JsonResponse
    {
        $groups = Group::with('user', 'categories', 'image', 'events')
            ->select([
                'groups.*',
                DB::raw('(COUNT(*)) as subscribes')
            ])
            ->leftJoin('group_participants', 'group_participants.group_id', '=', 'groups.id')
            ->groupBy('groups.id')
            ->orderBy('subscribes', 'DESC')
            ->get();

//        foreach($groups as $group) {
//            dd($group->categories);
//        }

        return response()->json($groups);
    }
}
