<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['stripe', 'paypal']),
            'class_name' => StripePaymentGateway::class,
            'is_active' => true,
            'config' => [
                'secret_key' => $this->faker->uuid(),
                'public_key' => $this->faker->uuid(),
                'webhook_secret' => $this->faker->uuid(),
            ]
        ];
    }

    /**
     * Configure the gateway as Stripe
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'stripe',
            'class_name' => StripePaymentGateway::class,
            'config' => [
                'secret_key' => 'sk_test_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'public_key' => 'pk_test_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
                'webhook_secret' => 'whsec_' . $this->faker->regexify('[A-Za-z0-9]{24}')
            ]
        ]);
    }

    /**
     * Configure the gateway as PayPal
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'paypal',
            'class_name' => 'App\Services\Payment\PayPalPaymentGateway',
            'config' => [
                'client_id' => $this->faker->regexify('[A-Za-z0-9]{32}'),
                'client_secret' => $this->faker->regexify('[A-Za-z0-9]{32}'),
                'mode' => 'sandbox'
            ]
        ]);
    }

    /**
     * Configure the gateway as inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false
        ]);
    }
}
