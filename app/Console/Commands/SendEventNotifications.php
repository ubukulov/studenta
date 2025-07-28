<?php

namespace App\Console\Commands;

use App\Jobs\PushNotificationJob;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEventNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Уведомленение о скором начинание ивента';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $events = Event::whereBetween('start_date', [Carbon::now(), Carbon::now()->addHours(3)])
                ->whereNull('notified_before_start')
                ->get();

        foreach ($events as $event) {
            $subscribes = EventParticipant::where(['event_id' => $event->id, 'status' => 'confirmed'])
                ->pluck('user_id')
                ->toArray();

            $users = User::select('id', 'device_token')
                    ->whereIn('id', $subscribes)
                    ->whereNotNull('device_token')
                    ->get();

            $notifications = [];
            $tokens = [];

            foreach ($users as $user) {
                $notifications[] = [
                    'user_id' => $user->id,
                    'type' => 'events',
                    'title' => $event->name,
                    'message' => "Скоро начнется!!!",
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
                    "Скоро начнется!!!",
                    ['type' => 'events', 'id' => (string) $event->id],
                );
            }

            $event->notified_before_start = Carbon::now();
            $event->save();
        }
    }
}
