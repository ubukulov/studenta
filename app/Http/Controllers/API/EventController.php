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
        $events = Event::with('user', 'group', 'images')
            ->selectRaw('events.*, event_participants.status')
            ->join('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->get();
        foreach($events as $event) {
            $event['participants'] = $event->getSubscribesCount();
            $event['subscribe'] = EventParticipant::userSubscribed($event->id, $this->user->id);
            unset($event['subscribes']);
        }

        return response()->json($events);
    }

    public function getEventById($id): \Illuminate\Http\JsonResponse
    {
        $event = Event::with('user', 'group', 'images')->findOrFail($id);
        return response()->json($event);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'group_id' => 'required',
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = $this->user->id;

        Event::create($data);

        return response()->json('Event успешно создан', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $event = Event::findOrFail($id);

        if($event->user_id != $this->user->id) {
            return response()->json('Ивент не ваша.', 400, [], JSON_UNESCAPED_UNICODE);
        }

        $request->validate([
            'group_id' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        $event->update($request->all());

        return response()->json('Ивент успешно обновлен', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $event = Event::findOrFail($id);
        if(!$event) {
            return response()->json('Ивент уже удалено', 400, [], JSON_UNESCAPED_UNICODE);
        }

        Event::destroy($id);

        return response()->json('Ивент удалено успешно', 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function subscribe(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'event_id' => 'required',
        ]);

        $event_id = $request->input('event_id');
        $event = Event::findOrFail($event_id);

        if(EventParticipant::userSubscribed($event_id, $this->user->id)) {
            return response()->json('Вы уже подписаны на этот ивент', 400, [], JSON_UNESCAPED_UNICODE);
        }

        EventParticipant::subscribe($event_id, $this->user->id, $event);

        if($event->type == 'free') {

            Notification::create([
                'user_id' => $this->user->id, 'type' => 'events', 'message' => "Вы успешно подписаны на ивент"
            ]);

            $this->firebase->sendNotification($this->user->device_token, 'Новое уведомление', "Вы успешно подписаны на ивент");

            return response()->json('Вы успешно подписаны на ивент', 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            Notification::create([
                'user_id' => $this->user->id, 'type' => 'events', 'message' => "Ждите подтверждение от модератора"
            ]);

            $this->firebase->sendNotification($this->user->device_token, 'Новое уведомление', "Ждите подтверждение от модератора");
            return response()->json('Ждите подтверждение от модератора', 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function unsubscribe(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'event_id' => 'required',
        ]);

        $event_id = $request->input('event_id');

        if(EventParticipant::userSubscribed($event_id, $this->user->id)) {
            Event::unSubscribe($event_id, $this->user->id);
            return response()->json('Вы успешно отписались от ивента', 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json('Вы не подписаны на ивент чтобы отписаться', 400, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function subscribedEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'images')
            ->selectRaw('events.*, event_participants.status')
            ->join('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->where('event_participants.user_id', $this->user->id)
            ->get();
        return response()->json($events);
    }

    public function getMyEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'images')
            ->where('user_id', $this->user->id)
            ->get();
        return response()->json($events);
    }

    public function confirmSubscribe(Request $request): \Illuminate\Http\JsonResponse
    {
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
            return response()->json("Для ивентов с типом БЕСПЛАТНО не нужно подтверждать подписки", 400, [], JSON_UNESCAPED_UNICODE);
        }

        $event_participant = EventParticipant::where(['event_id' => $event_id, 'user_id' => $user_id])->first();
        if($event_participant) {
            if($event_participant->status == 'confirmed') {
                return response()->json("Вы уже подтверждили подписку", 400, [], JSON_UNESCAPED_UNICODE);
            } else if($event_participant->status == 'rejected') {
                return response()->json("Вы уже отклонили подписку", 400, [], JSON_UNESCAPED_UNICODE);
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
    }

    public function getEvents(): \Illuminate\Http\JsonResponse
    {
        $events = Event::with('user', 'group', 'images')
            ->get();

        return response()->json($events);
    }

    public function getRequestsForSubscribe(): \Illuminate\Http\JsonResponse
    {
        $result = Event::where(['event_participants.status' => 'waiting', 'events.user_id' => $this->user->id])
            ->with('group', 'images')
            ->join('event_participants', 'events.id', '=', 'event_participants.event_id')
            ->get();

        $events = [];

        foreach($result as $item) {
            if(array_key_exists($item->event_id, $events)) {
                $events[$item->event_id]['users'][] = User::with('profile')->findOrFail($item->user_id);
            } else {
                $events[$item->event_id] = $item->toArray();
                $user = User::with('profile')->findOrFail($item->user_id);
                $events[$item->event_id]['users'][] = $user;
            }
        }

        $events = array_values($events);

        return response()->json($events);
    }
}
