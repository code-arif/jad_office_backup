<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    protected function getAccessToken()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.firebase.credentials'));
       
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion();

        return $token['access_token'] ?? null;
    }

    public function send($deviceToken, $title, $body, $data = [])
    {
        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/"
            . config('services.firebase.project_id')
            . "/messages:send";

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
            ]
        ];

        $response = Http::withToken($accessToken)
            ->post($url, $payload)
            ->json();

        // Log the response
        Log::info('Firebase Response', [
            'token' => $deviceToken,
            'payload' => $payload,
            'response' => $response
        ]);

        return $response;
    }
}
