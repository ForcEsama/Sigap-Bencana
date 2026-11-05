<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserNotificationWriter
{
    public function create(
        User $recipient,
        string $type,
        string $title,
        string $message,
        ?string $relatedType = null,
        ?int $relatedId = null,
    ): void {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $recipient->notifications()->create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
    }
}
