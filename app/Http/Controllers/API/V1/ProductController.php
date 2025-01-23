<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Product;
use App\Helpers\ResponseHelper;
use App\Traits\PaginatedResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Interfaces\ProductInterface;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    use PaginatedResponse;
    public function __construct(
        protected ProductInterface $productInterface
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        return $this->respondWithPagination(
            $this->productInterface->getPaginated([], $request->per_page),
            ProductResource::collection($this->productInterface->getPaginated([], $request->per_page)),
            'products',
            'Products fetched successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return ResponseHelper::success(
            new ProductResource($this->productInterface->create($request->validated())),
            'Product created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        return ResponseHelper::success(
            new ProductResource($product),
            'Product fetched successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->productInterface->update($product->id, $request->validated());
        return ResponseHelper::success(
            new ProductResource($product->fresh()),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        return ResponseHelper::success(
            $this->productInterface->destroy($product->id),
            'Product deleted successfully'
        );
    }
}
