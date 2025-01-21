<?php

namespace Tests\Unit\Controllers\API\V1;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private ProductController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(ProductController::class);
    }

    /** @test */
    public function it_returns_paginated_products()
    {
        // Arrange
        Product::factory()->count(15)->create();
        $request = new Request(['per_page' => 10]);

        // Act
        $response = $this->controller->index($request);

        // Assert
        $this->assertEquals(10, $response->count());
        $this->assertTrue($response->hasMorePages());
    }

    /** @test */
    public function it_creates_product_with_valid_data()
    {
        // Arrange
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'quantity' => 10
        ];

        $request = new StoreProductRequest($data);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals($data['name'], $response->name);
        $this->assertEquals($data['price'], $response->price);
        $this->assertEquals($data['quantity'], $response->quantity);
    }

    /** @test */
    public function it_updates_only_provided_fields()
    {
        // Arrange
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 100,
            'quantity' => 50
        ]);

        $updateData = ['name' => 'Updated Name'];
        $request = new UpdateProductRequest($updateData);

        // Act
        $response = $this->controller->update($request, $product);

        // Assert
        $this->assertEquals('Updated Name', $response->name);
        $this->assertEquals(100, $response->price);
        $this->assertEquals(50, $response->quantity);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
