<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test index returns unread notifications.
     */
    public function test_index_returns_unread_notifications(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 1',
            'data' => [],
            'read' => false,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 2',
            'data' => [],
            'read' => false,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 3 (read)',
            'data' => [],
            'read' => true,
        ]);

        $response = $this->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'notifications' => [
                '*' => ['id', 'user_id', 'type', 'message', 'data', 'read', 'read_at', 'user'],
            ],
        ]);

        $notifications = $response->json('notifications');
        $this->assertCount(2, $notifications);
        $this->assertFalse($notifications[0]['read']);
        $this->assertFalse($notifications[1]['read']);
    }

    /**
     * Test index limits to 10 notifications.
     */
    public function test_index_limits_to_10_notifications(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'updated',
                'message' => "User updated {$i}",
                'data' => [],
                'read' => false,
            ]);
        }

        $response = $this->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $notifications = $response->json('notifications');
        $this->assertCount(10, $notifications);
    }

    /**
     * Test index orders by created_at descending.
     */
    public function test_index_orders_by_created_at_descending(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $firstCreatedAt = now()->subMinutes(10);
        $secondCreatedAt = now();

        $first = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'First notification',
            'data' => [],
            'read' => false,
        ]);
        $first->created_at = $firstCreatedAt;
        $first->updated_at = $firstCreatedAt;
        $first->save();

        $second = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'Second notification',
            'data' => [],
            'read' => false,
        ]);
        $second->created_at = $secondCreatedAt;
        $second->updated_at = $secondCreatedAt;
        $second->save();

        $response = $this->getJson(route('notifications.index'));

        $response->assertStatus(200);
        $notifications = $response->json('notifications');
        $this->assertCount(2, $notifications);
        $this->assertEquals($second->id, $notifications[0]['id']);
        $this->assertEquals($first->id, $notifications[1]['id']);
    }

    /**
     * Test markAsRead marks notifications as read.
     */
    public function test_mark_as_read_marks_notifications_as_read(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification1 = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 1',
            'data' => [],
            'read' => false,
        ]);

        $notification2 = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 2',
            'data' => [],
            'read' => false,
        ]);

        $response = $this->postJson(route('notifications.mark-read'), [
            'notification_ids' => [$notification1->id, $notification2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification1->id,
            'read' => true,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification2->id,
            'read' => true,
        ]);

        $this->assertNotNull($notification1->fresh()->read_at);
        $this->assertNotNull($notification2->fresh()->read_at);
    }

    /**
     * Test markAsRead returns updated unread notifications.
     */
    public function test_mark_as_read_returns_updated_unread_notifications(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification1 = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 1',
            'data' => [],
            'read' => false,
        ]);

        $notification2 = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 2',
            'data' => [],
            'read' => false,
        ]);

        $notification3 = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated 3',
            'data' => [],
            'read' => false,
        ]);

        $response = $this->postJson(route('notifications.mark-read'), [
            'notification_ids' => [$notification1->id, $notification2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'notifications' => [
                '*' => ['id', 'user_id', 'type', 'message', 'data', 'read', 'read_at', 'user'],
            ],
        ]);

        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        $this->assertEquals($notification3->id, $notifications[0]['id']);
    }

    /**
     * Test markAsRead handles empty notification_ids array.
     */
    public function test_mark_as_read_handles_empty_notification_ids(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User updated',
            'data' => [],
            'read' => false,
        ]);

        $response = $this->postJson(route('notifications.mark-read'), [
            'notification_ids' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test markAsRead validates notification_ids is required.
     */
    public function test_mark_as_read_validates_notification_ids_required(): void
    {
        $response = $this->postJson(route('notifications.mark-read'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notification_ids']);
    }

    /**
     * Test markAsRead validates notification_ids exist.
     */
    public function test_mark_as_read_validates_notification_ids_exist(): void
    {
        $response = $this->postJson(route('notifications.mark-read'), [
            'notification_ids' => [999, 1000],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notification_ids.0', 'notification_ids.1']);
    }
}

