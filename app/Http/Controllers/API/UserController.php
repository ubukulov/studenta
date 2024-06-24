<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use File;

class UserController extends BaseApiController
{
    public function getProfile(): \Illuminate\Http\JsonResponse
    {
        $user_profile = UserProfile::where('user_id', $this->user->id)->first();
        return response()->json($user_profile, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function storeProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'city_id' => 'required',
            'university_id' => 'required',
            'specialty_id' => 'required',
            'start_year' => 'required',
            'end_year' => 'required',
        ]);

        $user = $this->user;
        $data = $request->all();
        $data['user_id'] = $user->id;

        if($request->hasFile('identity_card')) {
            $identity_card = $request->file('identity_card');
            $ext = $identity_card->getClientOriginalExtension();
            $name = md5(time()) . '.' . $ext;
            $path = '/upload/users/';
            $dir = public_path() . $path;
            $identity_card->move($dir, $name);
            $data['identity_card'] = $path.$name;
        }

        if($request->hasFile('student_card')) {
            $student_card = $request->file('student_card');
            $ext = $student_card->getClientOriginalExtension();
            $name = md5(time()) . '.' . $ext;
            $path = '/upload/users/';
            $dir = public_path() . $path;
            $student_card->move($dir, $name);
            $data['student_card'] = $path.$name;
        }

        if(User::hasProfile($user)) {
            $user_profile = UserProfile::findOrFail($user->id);
            $user_profile->update($data);
            return response()->json("Профиль успешно обновлено", 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            UserProfile::create($data);
            return response()->json("Профиль успешно создано", 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function deleteProfile($id): \Illuminate\Http\JsonResponse
    {
        $user_profile = UserProfile::findOrFail($id);
        if(!$user_profile) {
            return response()->json('Профиль уже удалено', 400, [], JSON_UNESCAPED_UNICODE);
        }

        $user_profile->delete();

        return response()->json('Профиль удалено успешно', 400, [], JSON_UNESCAPED_UNICODE);
    }

    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|min:8|string'
        ]);

        $user = $this->user;

        if(!Hash::check($request->get('current_password'), $this->user->password)) {
            return response()->json('Текущий пароль не правильно', 400, [], JSON_UNESCAPED_UNICODE);
        }

        if(strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
            return response()->json('Новый пароль не должен соответствовать текущему', 400, [], JSON_UNESCAPED_UNICODE);
        }

        $user->password = Hash::make($request->get('new_password'));
        $user->save();

        return response()->json('Пароль изменился успешно', 200, [], JSON_UNESCAPED_UNICODE);
    }
}
