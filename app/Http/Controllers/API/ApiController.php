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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Storage;
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

            Group::subscribe($user->id, Group::whereType('admin')->first()->id); // группа Saparline

            $token = $user->createToken('API TOKEN')->plainTextToken;

            return response()->json(['token' => $token], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json('Неверный код подтверждения', 422, [], JSON_UNESCAPED_UNICODE);
    }

    public function cities(): \Illuminate\Http\JsonResponse
    {
        return response()->json(City::all());
    }

    public function universities(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = University::with('city');
        if ($request->has('city_id') && !empty($request->city_id)) {
            $query = $query->where('city_id', $request->city_id);
        }
        $universities = $query->get();

        return response()->json($universities);
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
        $pp = "Пользовательское соглашение и Политика конфиденциальности приложения Studenta

1. Общие положения

1.1. Настоящее Пользовательское соглашение (далее – «Соглашение») регулирует отношения между физическим лицом — владельцем мобильного приложения Studenta (далее – «Администратор»), и пользователями приложения (далее – «Пользователь»). 1.2. Устанавливая и используя приложение Studenta, Пользователь подтверждает, что ознакомился с настоящим Соглашением и принимает его условия.

2. Назначение приложения

2.1. Приложение Studenta создано для студентов и предоставляет доступ к следующим возможностям:

просмотр акций и скидок от партнеров (еда, одежда, курсы, товары и пр.);
участие в мероприятиях и ивентах (дебаты, спорт, походы, встречи и пр.);
создание и участие в студенческих группах и сообществ;
получение информации о мероприятиях, организуемых администрацией приложения.
3. Персональные данные

3.1. При использовании приложения могут обрабатываться следующие данные Пользователя:

имя и фамилия (или никнейм);
электронная почта и/или номер телефона (для регистрации и связи);
данные о студенческом билете (для подтверждения статуса студента);
информация о действиях в приложении (участие в группах, ивентах).
3.2. Администратор обязуется:

использовать персональные данные только для целей работы приложения;
не передавать персональные данные третьим лицам без согласия Пользователя, кроме случаев, предусмотренных законодательством;
предпринимать меры по защите информации Пользователя.
3.3. Пользователь имеет право запросить удаление своих данных, обратившись на контактную почту Администратора.

4. Акции и купоны

4.1. В настоящий момент приложение Studenta не предоставляет купонов и не осуществляет возврат денежных средств. 4.2. В будущем, при внедрении функции купонов и платных услуг, условия их использования и возврата будут опубликованы в приложении и станут частью настоящего Соглашения.

5. Ответственность сторон

5.1. Администратор предоставляет приложение «как есть» и не несет ответственности за:

корректность и актуальность информации об акциях и мероприятиях, опубликованных пользователями;
возможные убытки Пользователя, возникшие из-за использования приложения;
действия третьих лиц (организаторов мероприятий, партнеров и т. д.).
5.2. Пользователь обязуется:

использовать приложение в рамках закона;
предоставлять достоверные данные при регистрации;
не распространять запрещенный контент (спам, оскорбления, экстремистские материалы и пр.).
6. Информационные сообщения

6.1. Пользователь соглашается получать уведомления от приложения (новости, акции, приглашения на мероприятия). 6.2. Пользователь может отказаться от рассылки в настройках приложения.

7. Изменение условий

7.1. Администратор оставляет за собой право изменять настоящее Соглашение. 7.2. Новая редакция вступает в силу с момента публикации в приложении.

8. Контакты

По вопросам, связанным с использованием приложения и защитой персональных данных, Пользователь может связаться с Администратором:

📧 Email: akhmetovmiras9@gmail.com 📍 Адрес: г. Алматы, Жубанова 3A";
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
        $promotions = Promotion::whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->with('category', 'organization', 'images')
            ->orderBy('size', 'DESC')
            ->get();

        foreach($promotions as $promotion){
            foreach($promotion->images as $image){
                if(!is_null($image->image)){
                    $image->image = Storage::disk('public')->url($image->image);
                }
                if(!is_null($image->video)){
                    $image->video = Storage::disk('public')->url($image->video);
                }
            }
        }
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
