<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'gateway' => $this->faker->randomElement(['stripe', 'paypal']),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'transaction_id' => $this->faker->uuid(),
            'gateway_response' => [
                'transaction_id' => $this->faker->uuid(),
                'status' => 'success',
                'message' => 'Payment processed successfully'
            ]
        ];
    }

    public function successful(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'successful'
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed'
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending'
        ]);
    }
}
