<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Product;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Interfaces\ProductInterface;
use Illuminate\Support\Facades\Request;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Repositories\ProductRepository;
class ProductController extends Controller
{
    public function __construct(
        private ProductRepository $productRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $products = $this->productRepository->getPaginated([], $request->input('per_page'));
        return ResponseHelper::success($products, 'Products fetched successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productRepository->create($request->validated());
        return ResponseHelper::success($product, 'Product created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        return ResponseHelper::success($product, 'Product fetched successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productRepository->update($product, $request->validated());
        return ResponseHelper::success($product, 'Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Check if product is used in any order
        if ($product->orderItems()->exists()) {
            return ResponseHelper::error('Cannot delete product that has been ordered', 400);
        }

        $this->productRepository->delete($product);
        return ResponseHelper::success(null, 'Product deleted successfully');
    }
}
