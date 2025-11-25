<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseMessageService
{
    public function sendToToken(string $deviceToken, string $title, string $body)
    {
        $messaging = app('firebase.messaging');

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(
                Notification::create($title, $body)
            );

        return $messaging->send($message);
    }
}
