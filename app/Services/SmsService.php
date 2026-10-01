<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsService
{
    public function send(
        string $phoneNumber,
        string $message
    ): void {
        $username = config(
            'services.africastalking.username'
        );

        $apiKey = config(
            'services.africastalking.api_key'
        );

        $senderId = config(
            'services.africastalking.sender_id'
        );

        if (!$username || !$apiKey) {
            throw new RuntimeException(
                'Africa\'s Talking SMS configuration is missing.'
            );
        }

        $response = Http::asForm()
            ->withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])
            ->post(
                'https://api.africastalking.com/version1/messaging',
                [
                    'username' => $username,
                    'to' => $phoneNumber,
                    'message' => $message,
                    'from' => $senderId,
                ]
            );

        if (!$response->successful()) {
            throw new RuntimeException(
                'SMS provider request failed.'
            );
        }
    }
}