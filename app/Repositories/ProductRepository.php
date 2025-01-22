<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ProductInterface;
use Illuminate\Database\Eloquent\Model;
use App\Exceptions\Product\ProductHasOrdersException;

class ProductRepository extends GeneralRepository implements ProductInterface
{
    public function __construct()
    {
        parent::__construct(new Product());
    }

    public function destroy(int $id): bool
    {
        $product = $this->model->findOrFail($id);
        
        if ($product->orderItems()->exists()) {
            throw new ProductHasOrdersException();
        }

        return parent::destroy($id);
    }
}
