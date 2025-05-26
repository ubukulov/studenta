<?php

namespace App\Classes;

use Carbon\Carbon;
use stdClass;

class Esputnik
{
    public function sendEmail($message_id, $data, $type_message = 1)
    {
        $url = env('ES_URL') . $message_id . '/smartsend';

        $json_value = new stdClass();
        $first_name = $data['name'];

        $confirmation_code = $data['code'];
        $json_value->recipients = [
            [
                'email' => $data['email'],
                'jsonParam' => "{'CODE': \"$confirmation_code\", 'FIRSTNAME': $first_name}"
            ]
        ];

        $this->sendRequestES($url, $json_value);
    }

    public function sendRequestES($url, $json_values)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_values));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_USERPWD, env('ES_USERNAME') . ':' . env('ES_PASSWORD'));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSLVERSION, 6);
        $output = curl_exec($ch);
        //echo($output);
        curl_close($ch);
    }
}
