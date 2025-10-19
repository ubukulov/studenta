<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\PushNotificationJob;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\GroupParticipant;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends BaseApiController
{
    public function events(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = $this->user->id;
        $query = Event::with('user', 'group', 'image')
            ->selectRaw('events.*, event_participants.status')
            ->leftJoin('event_participants', function ($join) use ($userId) {
                $join->on('events.id', '=', 'event_participants.event_id')
                    ->where('event_participants.user_id', '=', $userId);
            })
            ->whereDate('events.end_date', '>=', now()->toDateString());

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        $events = $query->get();

        foreach($events as $event) {
            if($event->user_id == $this->user->id) continue;
            $event['participants'] = $event->getSubscribesCount();
            $event['subscribe'] = EventParticipant::userSubscribed($event->id, $this->user->id);
            unset($event['subscribes']);
        }

        return response()->json($events);
    }

    public function getEventById($id): \Illuminate\Http\JsonResponse
    {
        $event = Event::with('user', 'group', 'image')->findOrFail($id);
        $event['participants'] = $event->getSubscribesCount();
        $event['subscribe'] = EventParticipant::userSubscribed($event->id, $this->user->id);
        return response()->json($event);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'group_id' => 'required',
                'name' => 'required',
            ]);

            $data = $request->all();
            $data['user_id'] = $this->user->id;

            $data['start_date'] = $data['date'] . " " . $data['start'];
            $data['end_date']   = $data['date'] . " " . $data['end'];

            $event = Event::create($data);
            $group = $event->group;

            // Кто подписан в группу, для всех отправляем push уведомление о создание новый ивент
            $subscribes = GroupParticipant::where(['group_id' => $event->group_id])
                ->pluck('user_id')
                ->toArray();

            $users = User::select('id', 'device_token')
                ->whereIn('id', $subscribes)
                ->whereNotNull('device_token')
                ->get();

            $notifications = [];
            $tokens = [];

            $notificationType = ($group->slug == 'studenta' && $group->type == 'admin') ? 'announcements' : 'events';

            foreach ($users as $user) {
                $notifications[] = [
                    'user_id' => $user->id,
                    'type' => $notificationType,
                    'title' => $event->name,
                    'message' => "Создан новый ивент",
                    'status'  => 'new',
                    'model_id' => $event->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                $tokens[] = $user->device_token;
            }

            Notification::insert($notifications);

            if (!empty($tokens)) {
                PushNotificationJob::dispatch(
                    $tokens,
                    $event->name,
                    "Создан новый ивент",
                    ['type' => $notificationType, 'id' => (string) $event->id],
                );
            }

            DB::commit();
            return response()->json('Event успешно создан', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $event = Event::findOrFail($id);

            if($event->user_id != $this->user->id) {
                return response()->json('Ивент не ваша.', 403, [], JSON_UNESCAPED_UNICODE);
            }

            $request->validate([
                'group_id' => 'required',
                'name' => 'required',
            ]);

            $event->update($request->all());

            return response()->json('Ивент успешно обновлен', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $event = Event::find($id);
        if(!$event) {
            return response()->json('Ивент уже удалено', 404, [], JSON_UNESCAPED_UNICODE);
        }

        Event::destroy($id);

        return response()->json('Ивент удалено успешно', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function subscribe(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'event_id' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $event_id = $request->input('event_id');
            $event = Event::findOrFail($event_id);
            $user = $event->user;

            if(EventParticipant::userSubscribed($event_id, $this->user->id)) {
                DB::commit();
                return response()->json('Вы уже подписаны на этот ивент', 409, [], JSON_UNESCAPED_UNICODE);
            }

            EventParticipant::subscribe($event_id, $this->user->id, $event);

            if($event->type == 'free') {

                /*Notification::create([
                    'user_id' => $this->user->id, 'type' => 'events', 'message' => "Вы успешно подписаны на ивент"
                ]);*/

                //$this->firebase->sendNotification($this->user->device_token, 'Новое уведомление', "Вы успешно подписаны на ивент", ['type' => 'events']);

                DB::commit();
                return response()->json('Вы успешно подписаны на ивент', 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                Notification::create([
                    'user_id' => $this->user->id, 'type' => 'events', 'message' => "Ждите подтверждение от модератора"
                ]);

                // отправляем пуш пользователю
                $this->firebase->sendNotification($this->user->device_token, 'Новое уведомление', "Ждите подтверждение от модератора", ['type' => 'events']);

                Notification::create([
                    'user_id' => $user->id, 'type' => 'events', 'message' => "Пользователь отправил запрос на подписку на ваш ивент. Не забудьте подтвердить"
                ]);
                // отправляем пуш к владелцу ивента
                $this->firebase->sendNotification($user->device_token, 'Новое уведомление', "Пользователь отправил запрос на подписку на ваш ивент. Не забудьте подтвердить", ['type' => 'events']);

                DB::commit();

                return response()->json('Ждите подтверждение от модератора', 200, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json($exception->getMessage(), 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function unsubscribe(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'event_id' => 'required',
            ]);

            $event_id = $request->input('event_id');

            if(EventParticipant::userSubscribed($event_id, $this->user->id)) {
                Event::unSubscribe($event_id, $this->user->id);
                return response()->json('Вы успешно отписались от ивента', 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                return response()->json('Вы не подписаны на ивент чтобы отписаться', 409, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function subscribedEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->selectRaw('events.*, event_participants.status')
            ->join('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->where('event_participants.user_id', $this->user->id)
            ->whereDate('events.end_date', '>=', date('Y-m-d'))
            ->get();
        return response()->json($events);
    }

    public function getMyEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->where('user_id', $this->user->id)
            ->whereDate('end_date', '>=', date('Y-m-d'))
            ->get();

        return response()->json($events);
    }

    public function confirmSubscribe(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'event_id' => 'required',
                'user_id' => 'required',
                'status' => 'required',
            ]);

            $event_id = $request->input('event_id');
            $user_id = $request->input('user_id');
            $status = $request->input('status');
            $event = Event::findOrFail($event_id);

            if($event->type == 'free') {
                return response()->json("Для ивентов с типом БЕСПЛАТНО не нужно подтверждать подписки", 409, [], JSON_UNESCAPED_UNICODE);
            }

            $event_participant = EventParticipant::where(['event_id' => $event_id, 'user_id' => $user_id])->first();
            $user = $event_participant->user;

            if($event_participant) {
                if($event_participant->status == 'confirmed') {
                    return response()->json("Вы уже подтверждили подписку", 409, [], JSON_UNESCAPED_UNICODE);
                } else if($event_participant->status == 'rejected') {
                    return response()->json("Вы уже отклонили подписку", 409, [], JSON_UNESCAPED_UNICODE);
                } else {
                    $event_participant->status = $status;
                    $event_participant->save();

                    if($status == 'rejected') {
                        $this->firebase->sendNotification($user->device_token, 'Новое уведомление', "Модератор отклонил ваш запрос", ['type' => 'events']);
                        Notification::create([
                            'user_id' => $user->id, 'type' => 'events', 'message' => "Модератор отклонил ваш запрос"
                        ]);
                        return response()->json("Вы отклонили подписку успешно", 200, [], JSON_UNESCAPED_UNICODE);
                    } else {
                        $this->firebase->sendNotification($user->device_token, 'Новое уведомление', "Модератор подтвердил. Вы участвуйте в ивенте", ['type' => 'events']);
                        Notification::create([
                            'user_id' => $user->id, 'type' => 'events', 'message' => "Модератор подтвердил. Вы участвуйте в ивенте"
                        ]);
                        return response()->json("Вы подтвердили подписку успешно", 200, [], JSON_UNESCAPED_UNICODE);
                    }
                }
            } else {
                return response()->json("Не найдено запись для подтверждение подписки", 404, [], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->get();

        return response()->json($events);
    }

    // список пользователей которые подали запрос на определенный ивент
    public function getRequestsForSubscribe($event_id): \Illuminate\Http\JsonResponse
    {
        /*$result = Event::where(['event_participants.status' => 'waiting', 'events.user_id' => $this->user->id])
            ->with('group', 'image')
            ->selectRaw('events.*, event_participants.user_id as subscribe_user_id, event_participants.event_id, event_participants.status')
            ->join('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->paginate(50);*/

        /*$events = [];

        foreach($result as $item) {
            if(array_key_exists($item->event_id, $events)) {
                $user = User::with('profile')->findOrFail($item->subscribe_user_id);
                $user = $user->toArray();
                $user['status'] = $item->status;
                $events[$item->event_id]['users'][] = $user;
            } else {
                $events[$item->event_id] = $item->toArray();
                $user = User::with('profile')->findOrFail($item->subscribe_user_id);
                $user = $user->toArray();
                $user['status'] = $item->status;
                $events[$item->event_id]['users'][] = $user;
            }
            unset($events[$item->event_id]['status']);
        }

        $events = array_values($events);

        return response()->json($events);*/

        $users = User::with('profile')
            ->selectRaw('users.*, event_participants.status')
            ->join('event_participants', 'users.id', '=', 'event_participants.user_id')
            ->where('event_participants.event_id', $event_id)
            ->paginate(50);

        return response()->json($users);

    }

    // список ивентов за которых буду участвовать или жду подтверждение от администратора
    public function getListOfEventsWhereSentRequests(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->selectRaw('events.*, event_participants.status')
            ->join('event_participants', 'event_participants.event_id', 'events.id')
            ->where('event_participants.user_id', $this->user->id)
            ->whereDate('events.end_date', '>=', date('Y-m-d'))
            ->paginate(50);
        return response()->json($events);
    }

    // список ивентов за которых прошлом участвовал
    public function historyOfEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->join('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->whereDate('events.end_date', '<', date('Y-m-d'))
            ->where('event_participants.user_id', $this->user->id)
            ->where('event_participants.status', '=', 'confirmed')
            ->paginate(50);
        return response()->json($events);
    }
}
