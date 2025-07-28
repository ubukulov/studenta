<?php

namespace App\Services;

use App\Models\User;
use Google\Client as GoogleClient;
use GuzzleHttp\Client;
class FirebaseService
{
    protected $googleClient;
    protected $httpClient;
    protected string $url;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $jsonPath = public_path(env('FIREBASE_URL_JSON'));

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("Firebase JSON-файл не найден: $jsonPath");
        }

        if (!env('FIREBASE_PROJECT_ID')) {
            throw new \RuntimeException("Не задан FIREBASE_PROJECT_ID в .env");
        }

        $this->googleClient = new GoogleClient();
        $this->googleClient->setAuthConfig($jsonPath);
        $this->googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $this->httpClient = new Client();

        $this->url = 'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send';
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $accessToken = $this->googleClient->fetchAccessTokenWithAssertion();

        if (isset($accessToken['error'])) {
            throw new \Exception('Ошибка при получении access token: ' . $accessToken['error_description']);
        }

        return $this->accessToken = $accessToken['access_token'];
    }

    protected function buildMessage(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null, ?string $clickAction = null): array
    {
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ],
        ];

        if ($imageUrl) {
            $message['message']['notification']['image'] = $imageUrl;
        }

        if ($clickAction) {
            $message['message']['data']['clickAction'] = $clickAction;
        }

        return $message;
    }

    public function sendNotification(string $token, string $title, string $body, array $data = [], ?string $imageUrl = null, ?string $clickAction = null): string
    {
        $message = $this->buildMessage($token, $title, $body, $data, $imageUrl, $clickAction);

        try {
            $response = $this->httpClient->post($this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при отправке уведомления: ' . $e->getMessage());
        }
    }

    public function sendNotificationToAllUsers(string $title, string $body, array $data = [], ?string $imageUrl = null, ?string $clickAction = null): void
    {
        $users = User::whereNotNull('device_token')->pluck('device_token');

        foreach ($users as $token) {
            $this->sendNotification($token, $title, $body, $data, $imageUrl, $clickAction);
        }
    }
}
