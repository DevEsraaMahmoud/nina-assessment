<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a notification can be created.
     */
    public function test_notification_can_be_created(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User has been updated.',
            'data' => ['key' => 'value'],
            'read' => false,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User has been updated.',
            'read' => false,
        ]);
    }

    /**
     * Test that a notification belongs to a user.
     */
    public function test_notification_belongs_to_user(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User has been updated.',
            'data' => ['key' => 'value'],
            'read' => false,
        ]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    /**
     * Test that data is cast to array.
     */
    public function test_data_is_cast_to_array(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User has been updated.',
            'data' => ['key' => 'value', 'number' => 123],
            'read' => false,
        ]);

        $this->assertIsArray($notification->data);
        $this->assertEquals('value', $notification->data['key']);
        $this->assertEquals(123, $notification->data['number']);
    }

    /**
     * Test that read is cast to boolean.
     */
    public function test_read_is_cast_to_boolean(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User has been updated.',
            'data' => [],
            'read' => false,
        ]);

        $this->assertIsBool($notification->read);
        $this->assertFalse($notification->read);

        $notification->update(['read' => true]);
        $this->assertTrue($notification->read);
    }

    /**
     * Test that read_at is cast to datetime.
     */
    public function test_read_at_is_cast_to_datetime(): void
    {
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'updated',
            'message' => 'User has been updated.',
            'data' => [],
            'read' => true,
            'read_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notification->read_at);
    }
}

