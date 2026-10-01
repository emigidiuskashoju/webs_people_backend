<?php

namespace App\Services;

use App\Models\DeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Throwable;

class FcmPushService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send a data + notification message to every device
     * registered to the given user.
     *
     * Used for chat messages.
     */
    public function sendToUser(
        int $userId,
        array $data,
        ?string $title = null,
        ?string $body = null,
    ): void {
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('fcm_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $message = CloudMessage::new();

        if ($title !== null) {
            $message = $message->withNotification(
                Notification::create($title, $body ?? '')
            );
        }

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[$key] = (string) $value;
        }
        $message = $message->withData($stringData);

        $message = $message->withAndroidConfig(
            AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'webs_people_messages_v2',
                    'sound' => 'default',
                ],
            ])
        );

        foreach ($tokens as $token) {
            try {
                $this->messaging->send(
                    $message->withChangedTarget('token', $token)
                );
            } catch (Throwable $e) {
                logger()->warning(
                    'FCM send failed',
                    [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
    }

    /**
     * Send an incoming call push to the given user.
     *
     * We send BOTH a notification block and a data block:
     *
     *   - The notification block lets Android show a
     *     heads-up notification while the app is in the
     *     background or killed.
     *   - The data block lets the Flutter app rebuild the
     *     full incoming-call UI when the user taps.
     *
     * The notification uses the `webs_people_calls_v2`
     * channel, which the app creates at startup with max
     * importance and a custom vibration pattern.
     */
    public function sendCallPush(
        int $recipientUserId,
        int $callerId,
        string $callerName,
        string $callerPhone,
        string $callId,
        string $type = 'audio',
    ): void {
        $tokens = DeviceToken::where(
            'user_id',
            $recipientUserId
        )
            ->pluck('fcm_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $message = CloudMessage::new();

        $message = $message->withNotification(
            Notification::create(
                'Incoming call',
                $callerName . ' is calling you',
            )
        );

        $message = $message->withData([
            'type' => 'incoming_call',
            'call_id' => (string) $callId,
            'caller_id' => (string) $callerId,
            'caller_name' => (string) $callerName,
            'caller_phone_number' => (string) $callerPhone,
            'call_type' => (string) $type,
        ]);

        $message = $message->withAndroidConfig(
            AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'webs_people_calls_v2',
                    'sound' => 'default',
                    'default_vibrate_timings' => false,
                    'vibrate_timings' => [
                        '0s',
                        '1s',
                        '0.5s',
                        '1s',
                        '0.5s',
                        '1s',
                    ],
                ],
            ])
        );

        foreach ($tokens as $token) {
            try {
                $this->messaging->send(
                    $message->withChangedTarget('token', $token)
                );
            } catch (Throwable $e) {
                logger()->warning(
                    'FCM call push failed',
                    [
                        'user_id' => $recipientUserId,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
    }
}