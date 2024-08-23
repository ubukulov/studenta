<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function getNotificationTypes(): \Illuminate\Http\JsonResponse
    {
        $arrTypes = [
            'user', 'announcements', 'promotions', 'events'
        ];

        $items = [];

        foreach ($arrTypes as $type) {
            $notification = Notification::whereType($type)
                    ->orderBy('id', 'desc')
                    ->first();
            if($notification) {
                $items[] = [
                    'name' => __('words.' . $type),
                    'type' => $type,
                    'date' => $notification->created_at,
                    'message' => $notification->message,
                ];
            } else {
                $items[] = [
                    'name' => __('words.' . $type),
                    'type' => $type,
                    'date' => null,
                    'message' => null,
                ];
            }
        }

        return response()->json($items);
    }

    public function getNotification($type): \Illuminate\Http\JsonResponse
    {
        $notifications = Notification::where(['type' => $type, 'user_id' => $this->user->id])
                ->with('user')
                ->get();
        return response()->json($notifications);
    }
}
