<?php

namespace App\Services;

use App\Models\SystemNotification;

class NotificationService
{
    public static function send($userId, $title, $message, $type = 'info', $referenceType = null, $referenceId = null)
    {
        SystemNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId
        ]);
    }
}