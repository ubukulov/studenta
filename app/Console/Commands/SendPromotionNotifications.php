<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Promotion;
use App\Models\User;
use App\Jobs\PushNotificationJob;
use Illuminate\Support\Facades\Storage;
class SendPromotionNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications for promotions starting now';

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
        $promotions = Promotion::with('images')
            ->whereDate('start_date', '<=', Carbon::now())
            ->where('notified', 0)
            ->get();

        foreach($promotions as $promotion){
            $imagePath = $promotion->images->first()?->image;
            $imageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;

            $message = "🔥 Новая акция!\n\n" .
                    $promotion->description;

            User::whereNotNull('device_token')
                ->select('id', 'device_token')
                ->chunk(500, function($users) use ($message, $promotion, $imageUrl) {
                    $now = Carbon::now();
                    $notifications = [];
                    $tokens = [];

                    foreach($users as $user){
                        $notifications[] = [
                            'user_id' => $user->id,
                            'type' => 'promotions',
                            'title' => $promotion->establishments_name,
                            'message' => $message,
                            'status'  => 'new',
                            'model_id' => $promotion->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $tokens[] = $user->device_token;
                    }

                    Notification::insert($notifications);

                    if (!empty($tokens)) {
                        PushNotificationJob::dispatch(
                            $tokens,
                            $promotion->establishments_name,
                            $message,
                            ['type' => 'promotions', 'id' => (string) $promotion->id],
                            $imageUrl
                        );
                    }

                });

            $promotion->update(['notified' => 1]);
        }
    }
}
