<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement(OrderStatus::cases())->value,
            'total_amount' => $this->faker->randomFloat(2, 100, 1000),
            'items' => [
                [
                    'name' => $this->faker->word(),
                    'quantity' => $this->faker->numberBetween(1, 5),
                    'price' => $this->faker->randomFloat(2, 10, 100)
                ]
            ],
            'customer_details' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->address()
            ],
            'notes' => $this->faker->sentence()
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PENDING->value
        ]);
    }

    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CONFIRMED->value
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED->value
        ]);
    }
}
