<?php

namespace Tests\Feature\Controllers\API\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $baseUrl = 'api/v1/products';
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user and generate JWT token
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        // Set the authorization header for all requests
        $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    protected function tearDown(): void
    {
        // Clean up by logging out if authenticated
        if (auth()->check()) {
            auth()->logout();
        }

        parent::tearDown();
    }

    #[Test]
    public function it_can_list_all_products()
    {
        // Arrange
        Product::factory()->count(3)->create();

        // Act
        $response = $this->getJson($this->baseUrl);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'products' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'stock',
                            'quantity',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    "pagination" => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page'
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data.products');
    }

    #[Test]
    public function it_can_create_a_product()
    {
        // Arrange
        $productData = [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'quantity' => $this->faker->numberBetween(1, 100)
        ];

        // Act
        $response = $this->postJson($this->baseUrl, $productData);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'quantity',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'price' => $productData['price'],
            'quantity' => $productData['quantity'],
        ]);
    }

    #[Test]
    public function it_can_show_a_product()
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->getJson("{$this->baseUrl}/{$product->id}");

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'created_at',
                    'updated_at',
                    'quantity'
                ]
            ])
            ->assertJsonPath('data.id', $product->id);
    }

    #[Test]
    public function it_can_update_a_product()
    {
        // Arrange
        $product = Product::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'price' => "189.99"
        ];

        // Act
        $response = $this->patchJson("{$this->baseUrl}/{$product->id}", $updateData);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.name', $updateData['name'])
            ->assertJsonPath('data.price', $updateData['price']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $updateData['name'],
            'price' => $updateData['price']
        ]);
    }

    #[Test]
    public function it_can_delete_a_product()
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->deleteJson("{$this->baseUrl}/{$product->id}");

        // Assert
        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_product()
    {
        // Act
        $response = $this->postJson($this->baseUrl, []);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'quantity']);
    }

    #[Test]
    public function it_validates_numeric_fields()
    {
        // Arrange
        $invalidData = [
            'name' => 'Test Product',
            'price' => 'not-a-number',
            'quantity' => 'not-a-number'
        ];

        // Act
        $response = $this->postJson($this->baseUrl, $invalidData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'quantity']);
    }

    #[Test]
    public function it_returns_404_for_non_existent_product()
    {
        // Act
        $response = $this->getJson("{$this->baseUrl}/99999");

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function it_validates_minimum_values_for_price_and_quantity()
    {
        // Arrange
        $invalidData = [
            'name' => 'Test Product',
            'price' => -1,
            'quantity' => -1
        ];

        // Act
        $response = $this->postJson($this->baseUrl, $invalidData);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'quantity']);
    }

    #[Test]
    public function it_cannot_delete_product_with_orders()
    {
        // Arrange
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'subtotal' => $product->price
        ]);

        // Act
        $response = $this->deleteJson("{$this->baseUrl}/{$product->id}");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot delete product that has been ordered'
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    #[Test]
    public function it_can_delete_product_without_orders()
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->deleteJson("{$this->baseUrl}/{$product->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
