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
            logger()->info('FCM: no tokens registered for user', [
                'user_id' => $userId,
            ]);
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
                $error = $e->getMessage();

                if (stripos($error, 'NotRegistered') !== false
                    || stripos($error, 'Requested entity was not found') !== false
                ) {
                    DeviceToken::where('fcm_token', $token)->delete();

                    logger()->info('FCM: removed stale token', [
                        'user_id' => $userId,
                        'token'   => substr($token, 0, 20) . '...',
                    ]);
                    continue;
                }

                logger()->warning('FCM send failed', [
                    'user_id' => $userId,
                    'error'   => $error,
                ]);
            }
        }
    }

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
            logger()->info('FCM call: no tokens registered', [
                'user_id' => $recipientUserId,
            ]);
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
                $error = $e->getMessage();

                if (stripos($error, 'NotRegistered') !== false
                    || stripos($error, 'Requested entity was not found') !== false
                ) {
                    DeviceToken::where('fcm_token', $token)->delete();

                    logger()->info('FCM call: removed stale token', [
                        'user_id' => $recipientUserId,
                        'token'   => substr($token, 0, 20) . '...',
                    ]);
                    continue;
                }

                logger()->warning('FCM call push failed', [
                    'user_id' => $recipientUserId,
                    'error'   => $error,
                ]);
            }
        }
    }
}
