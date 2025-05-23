<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ImageUpload;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use File;

class UserController extends BaseApiController
{
    public function getProfile(): \Illuminate\Http\JsonResponse
    {
        $user_profile = UserProfile::where('user_profile.user_id', $this->user->id)
            ->with('user', 'city', 'university', 'speciality')
            ->selectRaw('user_profile.*, identity.image as identity_card_image, student.image as student_card_image, avatar.image as avatar_image')
            ->leftJoin('image_uploads as identity', 'identity.id', '=', 'user_profile.identity_card')
            ->leftJoin('image_uploads as student', 'student.id', '=', 'user_profile.student_card')
            ->leftJoin('image_uploads as avatar', 'avatar.id', '=', 'user_profile.avatar')
            ->first();
        return response()->json($user_profile, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function storeProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->user;

        if($request->has('city_id')) $data['city_id'] = $request->get('city_id');
        if($request->has('university_id')) $data['university_id'] = $request->get('university_id');
        if($request->has('speciality_id')) $data['speciality_id'] = $request->get('speciality_id');
        if($request->has('start_year')) $data['start_year'] = $request->get('start_year');
        if($request->has('end_year')) $data['end_year'] = $request->get('end_year');
        if($request->has('identity_card')) $data['identity_card'] = $request->get('identity_card');
        if($request->has('student_card')) $data['student_card'] = $request->get('student_card');
        if($request->has('avatar')) $data['avatar'] = $request->get('avatar');


        $data = $request->all();

        $data['user_id'] = $user->id;

        if(isset($data['name'])) {
            $user->name = $data['name'];
            $user->save();
        }

        if(isset($data['surname'])) {
            $user->surname = $data['surname'];
            $user->save();
        }

        if(isset($data['phone'])) {
            $user->phone = $data['phone'];
            $user->save();
        }

        if(isset($data['device_token'])) {
            $user->device_token = $data['device_token'];
            $user->save();
        }

        if(User::hasProfile($user)) {
            $user_profile = UserProfile::where('user_id', $this->user->id)->first();
            $user_profile->update($data);
            return response()->json("Профиль успешно обновлено", 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            UserProfile::create($data);
            return response()->json("Профиль успешно создано", 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function deleteProfile(): \Illuminate\Http\JsonResponse
    {
        $user_profile = UserProfile::where('user_id', $this->user->id)->first();
        if(!$user_profile) {
            return response()->json('Профиль уже удалено', 409, [], JSON_UNESCAPED_UNICODE);
        }

        $user_profile->delete();

        return response()->json('Профиль удалено успешно', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|min:8|string'
            ]);

            $user = $this->user;

            if(!Hash::check($request->get('current_password'), $this->user->password)) {
                return response()->json('Текущий пароль не правильно', 422, [], JSON_UNESCAPED_UNICODE);
            }

            if(strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
                return response()->json('Новый пароль не должен соответствовать текущему', 422, [], JSON_UNESCAPED_UNICODE);
            }

            $user->password = Hash::make($request->get('new_password'));
            $user->save();

            return response()->json('Пароль изменился успешно', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required',
            ]);

            $image = $request->file('image');
            $ext = $image->getClientOriginalExtension();
            $name = md5(time()) . '.' . $ext;
            $path = '/upload/images/';
            $dir = public_path() . $path;
            $image->move($dir, $name);

            $imageUpload = ImageUpload::create([
                'user_id' => $this->user->id, 'image' => env('APP_URL') . $path.$name
            ]);

            return response()->json($imageUpload, 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function deleteAvatar(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'image_id' => 'required'
            ]);

            if(ImageUpload::get($this->user->id, $request->get('image_id'))) {
                ImageUpload::destroy($request->get('image_id'));

                UserProfile::resetAvatar($this->user);

                return response()->json('Фото успешно удалено', 200, [], JSON_UNESCAPED_UNICODE);
            }

            return response()->json('Не найдено фото с таким ид', 404, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
