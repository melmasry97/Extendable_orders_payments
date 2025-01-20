<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
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
        $orderData = [
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
            'items' => [
                [
                    'name' => 'Test Product 1',
                    'quantity' => 2,
                    'price' => 100.00
                ],
                [
                    'name' => 'Test Product 2',
                    'quantity' => 1,
                    'price' => 50.00
                ]
            ],
            'customer_details' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'address' => 'Test Address'
            ]
        ];

        $order = Order::create($orderData);

        $this->assertEquals(250.00, $order->total_amount);
    }

    public function test_order_casts_status_to_enum(): void
    {
        $this->assertInstanceOf(OrderStatus::class, $this->order->status);
    }

    public function test_order_casts_items_to_array(): void
    {
        $this->assertIsArray($this->order->items);
    }

    public function test_order_casts_customer_details_to_array(): void
    {
        $this->assertIsArray($this->order->customer_details);
    }
}
