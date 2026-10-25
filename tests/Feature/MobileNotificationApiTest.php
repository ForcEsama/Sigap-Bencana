<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_and_read_only_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('notification-test')->plainTextToken;
        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'title' => 'Laporan diterima',
            'message' => 'Laporan Anda tersimpan.',
            'type' => 'Aktivitas',
        ]);
        Notification::query()->create([
            'user_id' => $otherUser->id,
            'title' => 'Notifikasi privat',
            'message' => 'Milik pengguna lain.',
            'type' => 'Aktivitas',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/me/notifications?category=Aktivitas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.type', 'Aktivitas');

        $this->withToken($token)
            ->patchJson('/api/v1/me/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id);

        $this->withToken($token)
            ->patchJson('/api/v1/me/notifications/'.($notification->id + 1).'/read')
            ->assertNotFound();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_category_filter_rejects_unknown_values(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('notification-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/me/notifications?category=Unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }
}
