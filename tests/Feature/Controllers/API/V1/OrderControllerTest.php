<?php

namespace Tests\Feature\Controllers\API\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $baseUrl = 'api/v1/orders';
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        // Set the authorization header for all requests
        $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    #[Test]
    public function it_can_list_all_orders()
    {
        // Arrange
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->getJson($this->baseUrl);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'status',
                            'total_amount',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_create_order()
    {
        // Arrange
        $product = Product::factory()->create();
        $orderData = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => $product->price
                ]
            ]
        ];

        // Act
        $response = $this->postJson($this->baseUrl, $orderData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'status' => 'success',
                'message' => 'Order created successfully'
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING->value
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_order()
    {
        // Act
        $response = $this->postJson($this->baseUrl, []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    #[Test]
    public function it_can_show_order()
    {
        // Arrange
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->getJson("{$this->baseUrl}/{$order->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'user_id',
                    'status',
                    'total_amount',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    #[Test]
    public function it_can_update_order()
    {
        // Arrange
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $updateData = [
            'total_amount' => 100
        ];

        // Act
        $response = $this->patchJson("{$this->baseUrl}/{$order->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Order updated successfully'
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_can_delete_order()
    {
        // Arrange
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        // Act
        $response = $this->deleteJson("{$this->baseUrl}/{$order->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Order deleted successfully',
                'data' => true
            ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}
