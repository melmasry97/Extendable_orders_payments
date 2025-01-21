<?php

namespace App\Repositories;

use App\Models\Product;
use App\Interfaces\ProductInterface;

class ProductRepository extends GeneralRepository implements ProductInterface
{
    public function __construct()
    {
        parent::__construct(new Product());
    }

}
