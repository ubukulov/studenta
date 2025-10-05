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

    public function getNotificationCount()
    {
        $notifications = Notification::where('user_id', $this->user->id)
            ->get();

        $grouped = $notifications->groupBy('type');

        $allowedTypes = ['user', 'announcements', 'promotions', 'events'];

        /*$notificationTypes = collect($allowedTypes)->map(function ($type) use ($grouped) {
            $group = $grouped->get($type, collect());
            $first = $group->first();

            return [
                'type' => $type,
                'count' => $group->count(),
                'title' => $first?->title ?? null,
                'message' => $first?->message ?? null,
                'image' => $first?->image ?? null,
                'date' => optional($first?->created_at)->toDateString(),
            ];
        });

        return [
            'totalCount' => $notifications->count(),
            'notification_types' => $notificationTypes->toArray(),
        ];*/

        $data = [
            'totalCount' => Notification::where(['user_id' => $this->user->id, 'status' => 'new'])->count()
        ];

        $notificationUser = Notification::where(['user_id' => $this->user->id, 'type' => 'user'])->orderBy('id', 'desc')->first();
        $notificationAnnouncement = Notification::where(['user_id' => $this->user->id, 'type' => 'announcements'])->orderBy('id', 'desc')->first();
        $notificationPromotion = Notification::where(['user_id' => $this->user->id, 'type' => 'promotions'])->orderBy('id', 'desc')->first();
        $notificationEvent = Notification::where(['user_id' => $this->user->id, 'type' => 'events'])->orderBy('id', 'desc')->first();

        $data['notification_types'] = [
            [
                "type" => 'user',
                'count' => Notification::where(['user_id' => $this->user->id, 'status' => 'new', 'type' => 'user'])->count(),
                'title' => 'Пользователь',
                'message' => ($notificationUser) ? $notificationUser->message : null,
                'image' => env('APP_URL') . "/files/user.png",
            ],
            [
                "type" => 'announcements',
                'count' => Notification::where(['user_id' => $this->user->id, 'status' => 'new', 'type' => 'announcements'])->count(),
                'title' => 'Объявления',
                'message' => ($notificationAnnouncement) ? $notificationAnnouncement->message : null,
                'image' => env('APP_URL') . "/files/announcements.png",
            ],
            [
                "type" => 'promotions',
                'count' => Notification::where(['user_id' => $this->user->id, 'status' => 'new', 'type' => 'promotions'])->count(),
                'title' => 'Акции',
                'message' => ($notificationPromotion) ? $notificationPromotion->message : null,
                'image' => env('APP_URL') . "/files/promotions.png",
            ],
            [
                "type" => 'events',
                'count' => Notification::where(['user_id' => $this->user->id, 'status' => 'new', 'type' => 'events'])->count(),
                'title' => 'Ивенты',
                'message' => ($notificationEvent) ? $notificationEvent->message : null,
                'image' => env('APP_URL') . "/files/events.png",
            ],
        ];

        return $data;

        //return Notification::where(['user_id' => $this->user->id, 'status' => 'new', 'type' => $type])->count();
    }

    public function updateNotification(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'notification_id' => 'required',
            ]);

            $notification = Notification::findOrFail($request->input('notification_id'));

            Notification::where(['user_id' => $this->user->id, 'type' => $notification->type, 'status' => 'new'])
                    ->update(['status' => 'read']);

            return response()->json('Уведомление прочитано успешно', 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json($e->validator->errors(), 422, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
