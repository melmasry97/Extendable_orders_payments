<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Interfaces\ProductInterface;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends GeneralRepository implements ProductInterface
{
    public function __construct()
    {
        parent::__construct(new Product());
    }

    public function destroy(int $id): bool
    {
        if ($this->model->orderItems()->exists()) {
            return false;
        }
        return parent::destroy($id);
    }
}
