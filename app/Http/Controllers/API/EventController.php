<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends BaseApiController
{
    public function events(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->selectRaw('events.*, event_participants.status')
            ->leftJoin('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->get();
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
        return response()->json($event);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'group_id' => 'required',
                'name' => 'required',
            ]);

            $data = $request->all();
            $data['user_id'] = $this->user->id;

            Event::create($data);

            return response()->json('Event успешно создан', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
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

            if(EventParticipant::userSubscribed($event_id, $this->user->id)) {
                DB::commit();
                return response()->json('Вы уже подписаны на этот ивент', 409, [], JSON_UNESCAPED_UNICODE);
            }

            EventParticipant::subscribe($event_id, $this->user->id, $event);

            if($event->type == 'free') {

                Notification::create([
                    'user_id' => $this->user->id, 'type' => 'events', 'message' => "Вы успешно подписаны на ивент"
                ]);

                //$this->firebase->sendNotification($this->user->device_token, 'Новое уведомление', "Вы успешно подписаны на ивент");

                DB::commit();
                return response()->json('Вы успешно подписаны на ивент', 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                Notification::create([
                    'user_id' => $this->user->id, 'type' => 'events', 'message' => "Ждите подтверждение от модератора"
                ]);

                //$this->firebase->sendNotification($this->user->device_token, 'Новое уведомление', ['text' => "Ждите подтверждение от модератора"]);

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
            ->get();
        return response()->json($events);
    }

    public function getMyEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'image')
            ->where('user_id', $this->user->id)
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
            if($event_participant) {
                if($event_participant->status == 'confirmed') {
                    return response()->json("Вы уже подтверждили подписку", 409, [], JSON_UNESCAPED_UNICODE);
                } else if($event_participant->status == 'rejected') {
                    return response()->json("Вы уже отклонили подписку", 409, [], JSON_UNESCAPED_UNICODE);
                } else {
                    $event_participant->status = $status;
                    $event_participant->save();

                    if($status == 'rejected') {
                        return response()->json("Вы отклонили подписку успешно", 200, [], JSON_UNESCAPED_UNICODE);
                    } else {
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
