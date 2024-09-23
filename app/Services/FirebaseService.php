<?php

namespace App\Services;

use Google\Client as GoogleClient;
use GuzzleHttp\Client;
class FirebaseService
{
    protected $googleClient;
    protected $httpClient;

    public function __construct()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setAuthConfig(public_path('files/saparline-studenta.json'));
        $this->googleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $this->httpClient = new Client();
    }

    public function sendNotification($token, $title, $body, $data = [])
    {
        $url = 'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send';

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

        $accessToken = $this->googleClient->fetchAccessTokenWithAssertion()['access_token'];

        $response = $this->httpClient->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $message,
        ]);

        return $response->getBody()->getContents();
    }
}
