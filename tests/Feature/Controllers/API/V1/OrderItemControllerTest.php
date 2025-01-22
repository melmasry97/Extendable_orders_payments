<?php

namespace Tests\Feature\Controllers\API\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Payment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

class OrderItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
        $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function it_can_add_items_to_order()
    {
        // Arrange
        $order = Order::factory()->create();
        $product = Product::factory()->create(['price' => 100]);

        $data = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2
                ]
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/orders/{$order->id}/items", $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('status')
                    ->where('status', 'success')
                    ->where('message', 'Items added successfully')
                    ->has('data', fn (AssertableJson $json) =>
                        $json->first(fn ($json) =>
                            $json->has('id')
                                ->where('order_id', $order->id)
                                ->where('product_id', $product->id)
                                ->where('quantity', 2)
                                ->where('unit_price', function ($value) use ($product) {
                                    return $value == (float) $product->price;
                                })
                                ->where('subtotal', function ($value) use ($product) {
                                    return $value == (float) $product->price * 2;
                                })
                                ->has('created_at')
                                ->has('updated_at')
                                ->has('product', fn ($json) =>
                                    $json->where('id', $product->id)
                                        ->has('name')
                                        ->has('description')
                                        ->where('price', (string) $product->price)
                                        ->has('quantity')
                                        ->etc()
                                )
                                ->etc()
                        )
                    )
            );

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->price,
            'subtotal' => $product->price * 2
        ]);
    }

    #[Test]
    public function it_can_update_order_item()
    {
        // Arrange
        $order = Order::factory()->create();
        $product = Product::factory()->create(['price' => 100]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'subtotal' => $product->price
        ]);

        $data = ['quantity' => 3];

        // Act
        $response = $this->putJson("/api/v1/orders/{$order->id}/items/{$orderItem->id}", $data);

        // Assert
        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data')
                    ->where('data.quantity', 3)
                    ->where('data.subtotal', function ($value) use ($product) {
                        return $value == (float) $product->price * 3;
                    })
                    ->etc()
            );

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'quantity' => 3,
            'subtotal' => 300.00
        ]);
    }

    #[Test]
    public function it_can_remove_item_from_order()
    {
        // Arrange
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/orders/{$order->id}/items/{$orderItem->id}");

        // Assert
        $response->assertOk()
            ->assertJson(['message' => 'Item removed successfully']);

        $this->assertDatabaseMissing('order_items', ['id' => $orderItem->id]);
    }

    #[Test]
    public function it_cannot_add_items_to_non_existent_order()
    {
        // Arrange
        $product = Product::factory()->create();
        $nonExistentOrderId = 99999;

        $data = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2
                ]
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/orders/{$nonExistentOrderId}/items", $data);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function it_validates_required_fields_when_adding_items()
    {
        // Arrange
        $order = Order::factory()->create();

        // Act
        $response = $this->postJson("/api/v1/orders/{$order->id}/items", []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    #[Test]
    public function it_validates_items_array_structure()
    {
        // Arrange
        $order = Order::factory()->create();
        $data = [
            'items' => [
                [
                    // Missing product_id and quantity
                ]
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/orders/{$order->id}/items", $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items.0.product_id',
                'items.0.quantity'
            ]);
    }

}
