<?php

namespace App\Exceptions\Product;

use App\Exceptions\ApiException;

class ProductHasOrdersException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Cannot delete product that has been ordered',
            code: 400
        );
    }
}
