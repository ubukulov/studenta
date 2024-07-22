<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmationEmail;
use App\Mail\ResetPasswordEmail;
use App\Models\Category;
use App\Models\City;
use App\Models\ConfirmationCode;
use App\Models\Interest;
use App\Models\Organization;
use App\Models\Speciality;
use App\Models\University;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Auth;
use Validator;

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
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 401);
            }

            $input = $request->all();
            $input['code'] = rand(100000,999999);

            $confirmation_code = ConfirmationCode::create($input);

            $data = [
                'title' => 'Подтвердите регистрацию',
                'code' => $confirmation_code->code,
            ];

            Mail::to($confirmation_code->email)->send(new ConfirmationEmail($data));

            return response()->json('Код подтверждение регистрации отправлено на вашу почту', 200,[],JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
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
                'email' => $data['email'], 'password' => bcrypt($confirmation_code->password)
            ]);

            $token = $user->createToken('API TOKEN')->plainTextToken;

            return response()->json(['token' => $token], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json('Неверный код подтверждения', 401, [], JSON_UNESCAPED_UNICODE);
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
                return response()->json('Пользователь с таким email не найдено', 403, [], JSON_UNESCAPED_UNICODE);
            }

            $new_password = rand(10000000,99999999);
            $user->password = bcrypt($new_password);
            $user->save();

            $data = [
                'title' => 'Вы сбросили пароль',
                'password' => $new_password
            ];

            Mail::to($email)->send(new ResetPasswordEmail($data));

            return response()->json('Новый пароль успешно отправлено на почту', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
