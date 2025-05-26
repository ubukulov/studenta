<?php

namespace App\Classes;

use Carbon\Carbon;
use stdClass;

class Esputnik
{
    public function sendEmail($message_id, $data, $type_message = 1)
    {
        $url = env('ES_URL') . $message_id . '/smartsend';

        $json_value = [
            'recipients' => [
                [
                    'email' => $data['email'],
                    'params' => [
                        'FIRSTNAME' => $data['name'],
                        'CODE' => $data['code'],
                    ],
                ],
            ],
        ];

        $this->sendRequestES($url, $json_value);
    }

    public function sendRequestES($url, $data): bool
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(env('ES_USERNAME') . ':' . env('ES_PASSWORD')),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        if($response->getStatusCode() == 200){
            return true;
        }

        return false;
    }
}
