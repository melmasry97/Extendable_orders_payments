<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\OrderItem;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    public function test_order_belongs_to_user(): void
    {
        $this->assertInstanceOf(User::class, $this->order->user);
        $this->assertEquals($this->user->id, $this->order->user->id);
    }

    public function test_order_has_many_payments(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->order->payments);
    }

    public function test_order_can_be_deleted_when_no_payments(): void
    {
        $this->assertTrue($this->order->canBeDeleted());
    }

    public function test_order_cannot_be_deleted_with_payments(): void
    {
        Payment::factory()->create([
            'order_id' => $this->order->id
        ]);

        $this->assertFalse($this->order->canBeDeleted());
    }

    public function test_order_can_process_payment_when_confirmed(): void
    {
        $order = Order::factory()->confirmed()->create();
        $this->assertTrue($order->canProcessPayment());
    }

    public function test_order_cannot_process_payment_when_pending(): void
    {
        $order = Order::factory()->pending()->create();
        $this->assertFalse($order->canProcessPayment());
    }

    public function test_order_cannot_process_payment_when_cancelled(): void
    {
        $order = Order::factory()->cancelled()->create();
        $this->assertFalse($order->canProcessPayment());
    }

    public function test_order_calculates_total_amount_on_creation(): void
    {
        // Create products with known prices
        $product1 = Product::factory()->create(['price' => 100]);
        $product2 = Product::factory()->create(['price' => 50]);

        // Create order
        $order = Order::create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 0
        ]);

        // Create order items
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => $product1->price,
            'subtotal' => $product1->price * 2
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => $product2->price,
            'subtotal' => $product2->price
        ]);

        // Refresh order to get the updated total
        $order->refresh();

        // Assert total amount (2 * 100 + 1 * 50 = 250)
        $this->assertEquals(250.00, $order->total_amount);
    }

    public function test_order_casts_status_to_enum(): void
    {
        $this->assertInstanceOf(OrderStatus::class, $this->order->status);
    }

}
