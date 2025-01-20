<?php

namespace Tests\Feature\API\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private array $orderData;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->orderData = [
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 2,
                    'price' => 100.00
                ]
            ],
            'customer_details' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '1234567890',
                'address' => '123 Test St'
            ],
            'notes' => 'Test order notes'
        ];
    }

    public function test_user_can_create_order(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/orders', $this->orderData);

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
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items', 'customer_details']);
    }

    public function test_user_can_view_their_orders(): void
    {
        Sanctum::actingAs($this->user);

        Order::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/v1/orders');

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
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $order->id,
                    'user_id' => $this->user->id
                ]
            ]);
    }

    public function test_user_can_update_order(): void
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'status' => OrderStatus::CONFIRMED->value,
            'notes' => 'Updated notes'
        ];

        $response = $this->putJson("/api/v1/orders/{$order->id}", $updateData);

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
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->putJson("/api/v1/orders/{$order->id}", [
            'status' => 'invalid-status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_delete_order(): void
    {
        Sanctum::actingAs($this->user);

        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order deleted successfully'
            ]);

        $this->assertDatabaseMissing('orders', [
            'id' => $order->id
        ]);
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Sanctum::actingAs($this->user);

        // Create orders with different statuses
        Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING
        ]);
        Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::CONFIRMED
        ]);

        $response = $this->getJson('/api/v1/orders?status=' . OrderStatus::PENDING->value);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals(OrderStatus::PENDING->value, $response->json('data.data.0.status'));
    }
}
