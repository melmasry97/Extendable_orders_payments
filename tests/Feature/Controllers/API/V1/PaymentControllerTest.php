<?php

namespace Tests\Feature\Controllers\API\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Enums\OrderStatus;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private PaymentGateway $gateway;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        $this->order = Order::factory()
            ->confirmed()
            ->create([
                'user_id' => $this->user->id,
                'total_amount' => 100.00
            ]);

        $this->gateway = PaymentGateway::factory()
            ->stripe()
            ->create();

        $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    public function test_can_process_payment(): void
    {
        $paymentData = [
            'gateway' => $this->gateway->name,
            'payment_method' => 'card',
            'card_number' => '4242424242424242',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $response = $this->postJson("/api/v1/orders/{$this->order->id}/payments", $paymentData);

        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('status')
                    ->where('status', 'success')
                    ->has('message')
                    ->has('data', fn ($json) =>
                        $json->has('id')
                            ->where('order_id', $this->order->id)
                            ->where('amount', function ($amount) {
                                return $amount == 100.00;
                            })
                            ->has('status')
                            ->has('transaction_id')
                            ->has('gateway')
                            ->has('gateway_response')
                            ->etc()
                    )
            );

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'payment_gateway_id' => $this->gateway->id,
            'amount' => 100.00
        ]);
    }

    public function test_cannot_process_payment_for_non_confirmed_order(): void
    {
        $pendingOrder = Order::factory()
            ->pending()
            ->create(['user_id' => $this->user->id]);

        $paymentData = [
            'gateway' => $this->gateway->name,
            'payment_method' => 'card',
            'card_number' => '4242424242424242',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123'
        ];

        $response = $this->postJson("/api/v1/orders/{$pendingOrder->id}/payments", $paymentData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Payments can only be processed for confirmed orders'
            ]);
    }

    public function test_cannot_process_payment_with_invalid_gateway(): void
    {
        $paymentData = [
            'gateway' => 'invalid_gateway',
            'payment_method' => 'card'
        ];

        $response = $this->postJson("/api/v1/orders/{$this->order->id}/payments", $paymentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gateway']);
    }

    public function test_can_get_order_payments(): void
    {
        Payment::factory()->count(3)->create([
            'order_id' => $this->order->id,
            'payment_gateway_id' => $this->gateway->id
        ]);

        $response = $this->getJson("/api/v1/orders/{$this->order->id}/payments");

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('status')
                    ->where('status', 'success')
                    ->has('message')
                    ->has('data.payments', 3)
                    ->has('data.pagination')
            );
    }

    public function test_can_get_payment_details(): void
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'payment_gateway_id' => $this->gateway->id
        ]);

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('status')
                    ->where('status', 'success')
                    ->has('message')
                    ->has('data', fn ($json) =>
                        $json->where('id', $payment->id)
                            ->has('order_id')
                            ->has('amount')
                            ->has('status')
                            ->has('transaction_id')
                            ->has('gateway')
                            ->etc()
                    )
            );
    }

    public function test_validates_required_payment_fields(): void
    {
        $response = $this->postJson("/api/v1/orders/{$this->order->id}/payments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'gateway',
                'payment_method'
            ]);
    }

    public function test_validates_card_payment_fields(): void
    {
        $paymentData = [
            'gateway' => $this->gateway->name,
            'payment_method' => 'card'
        ];

        $response = $this->postJson("/api/v1/orders/{$this->order->id}/payments", $paymentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'card_number',
                'expiry_month',
                'expiry_year',
                'cvv'
            ]);
    }
}
