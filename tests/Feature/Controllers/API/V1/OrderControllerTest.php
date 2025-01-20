<?php

namespace Tests\Feature\Controllers\API\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private array $orderData;
    private string $token;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => bcrypt('password')
        ]);

        $this->token = JWTAuth::fromUser($this->user);

        $this->orderData = [
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 2,
                    'price' => 100.00
                ]
            ],
            'customer_details' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'address' => $this->user->address
            ],
            'notes' => 'Test order notes'
        ];
    }

    public function test_user_cannot_access_orders_without_token(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_user_can_create_order(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/orders', $this->orderData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order created successfully'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'total_amount',
                    'items',
                    'customer_details',
                    'notes'
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING->value,
            'total_amount' => 200.00
        ]);
    }

    public function test_user_cannot_create_order_with_invalid_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items', 'customer_details']);
    }

    public function test_user_can_view_their_orders(): void
    {
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'status',
                            'total_amount'
                        ]
                    ]
                ]
            ]);
    }

    public function test_user_can_view_single_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $order->id,
                    'user_id' => $this->user->id
                ]
            ]);
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'status' => OrderStatus::CONFIRMED->value,
            'notes' => 'Updated notes'
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/orders/{$order->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order updated successfully'
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CONFIRMED->value,
            'notes' => 'Updated notes'
        ]);
    }

    public function test_user_cannot_update_order_with_invalid_status(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/orders/{$order->id}", [
                'status' => 'invalid-status'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_delete_order(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order deleted successfully'
            ]);

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id
        ]);
    }

    public function test_user_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create a payment for the order
        Payment::factory()->create([
            'order_id' => $order->id
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot delete order with existing payments'
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id
        ]);
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        // Create orders with different statuses
        Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING
        ]);
        Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::CONFIRMED
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/orders?status=' . OrderStatus::PENDING->value);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals(OrderStatus::PENDING->value, $response->json('data.data.0.status'));
    }
}
