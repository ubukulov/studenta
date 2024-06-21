<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends BaseApiController
{
    public function events()
    {
        $events = Event::with('user', 'group', 'images')
            ->get();
        foreach($events as $event) {
            $event['subscribes'] = count($event->subscribes);
            $event['subscribe'] = (EventParticipant::userSubscribed($event->id, $this->user->id)) ? true : false;
        }
        return response()->json($events);
    }

    public function getEventById($id)
    {
        $event = Event::with('user', 'group', 'images')->findOrFail($id);
        return response()->json($event);
    }

    public function store(Request $request)
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

    public function update(Request $request, $id)
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

    public function delete($id)
    {
        $event = Event::findOrFail($id);
        if(!$event) {
            return response()->json('Ивент уже удалено', 400, [], JSON_UNESCAPED_UNICODE);
        }

        Event::destroy($id);

        return response()->json('Ивент удалено успешно', 400, [], JSON_UNESCAPED_UNICODE);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'event_id' => 'required',
        ]);

        $event_id = $request->input('event_id');

        if(EventParticipant::userSubscribed($event_id, $this->user->id)) {
            return response()->json('Вы уже подписаны на этот ивент', 400, [], JSON_UNESCAPED_UNICODE);
        } else {
            EventParticipant::subscribe($event_id, $this->user->id);
            return response()->json('Вы успешно подписаны на ивент', 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function unsubscribe(Request $request)
    {
        $request->validate([
            'event_id' => 'required',
        ]);

        $event_id = $request->input('event_id');

        if($event_participant = EventParticipant::userSubscribed($event_id, $this->user->id)) {
            Event::unSubscribe($event_participant);
            return response()->json('Вы успешно отписались от ивента', 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json('Вы не подписаны на ивент чтобы отписаться', 400, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
