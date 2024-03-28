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
}
