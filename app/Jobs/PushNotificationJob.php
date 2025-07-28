<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FirebaseService;

class PushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $tokens;
    protected string $title;
    protected string $body;
    protected array $data;
    protected ?string $imageUrl;
    protected ?string $clickAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        array|string $tokens,
        string $title,
        string $body,
        array $data = [],
        ?string $imageUrl = null,
        ?string $clickAction = null
    ) {
        $this->tokens = is_array($tokens) ? $tokens : [$tokens];
        $this->title = $title;
        $this->body = $body;
        $this->data = array_map('strval', $data);
        $this->imageUrl = $imageUrl;
        $this->clickAction = $clickAction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FirebaseService $firebase): void
    {
        \Log::info('👷 PushNotificationJob стартовал', [
            'tokens' => $this->tokens,
            'title' => $this->title,
        ]);
        foreach ($this->tokens as $token) {
            try {
                $firebase->sendNotification(
                    $token,
                    $this->title,
                    $this->body,
                    $this->data,
                    $this->imageUrl,
                    $this->clickAction
                );
            } catch (\Exception $e) {
                \Log::warning("Ошибка при отправке push-токену {$token}: " . $e->getMessage());
            }
        }
        \Log::info('✅ PushNotificationJob завершён');
    }
}
