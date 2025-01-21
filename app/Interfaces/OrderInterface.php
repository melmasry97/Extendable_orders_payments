<?php

namespace App\Interfaces;

use App\Interfaces\GeneralInterface;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderInterface extends GeneralInterface
{
    public function getOrderWithDetails(int $id): ?Order;
    public function getAllPaginated(int $perPage = 15, string $status = null): LengthAwarePaginator;
}
