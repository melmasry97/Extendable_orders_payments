<?php

namespace App\Repositories;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\OrderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository extends GeneralRepository implements OrderInterface
{
    public function __construct()
    {
        parent::__construct(new Order());
    }

    /**
     * @param array $input
     * @return Model
     */
    public function create(array $input): Model
    {
        return DB::transaction(function () use ($input) {
            $order = parent::create([
                'user_id' => auth()->id(),
                'status' => OrderStatus::PENDING,
                'total_amount' => 0
            ]);

            if (isset($input['items'])) {
                foreach ($input['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['quantity'] * $item['unit_price']
                    ]);
                }

                $order->update([
                    'total_amount' => $order->items->sum('subtotal')
                ]);
            }

            return $order->fresh(['user', 'items.product']);
        });
    }

    /**
     * @param array $input
     * @return bool
     */
    public function update(int $id, array $input): bool
    {
        return DB::transaction(function () use ($input) {
            if (isset($input['status']) &&
                $input['status'] === OrderStatus::CANCELLED->value &&
                $this->model->payments()->exists()
            ) {
                throw new \Exception('Cannot cancel an order with payments');
            }

            $this->model->update($input);
            return $this->model->fresh();
        });
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        return DB::transaction(function () {
            if ($this->model->payments()->exists()) {
                return false;
            }

            $this->model->items()->delete();
            return parent::delete();
        });
    }

    /**
     * @param int $id
     * @return Order|null
     */
    public function getOrderWithDetails(int $id): ?Order
    {
        return $this->model->with(['user', 'items.product', 'payments'])
            ->findOrFail($id);
    }

    public function getAllPaginated(int $perPage = 15, string $status = null): LengthAwarePaginator
    {
        $query = $this->model->with(['user']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }
}
