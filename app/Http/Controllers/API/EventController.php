<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends BaseApiController
{
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
}
