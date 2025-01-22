<?php

namespace App\Repositories;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Interfaces\OrderInterface;
use App\Interfaces\OrderItemInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository extends GeneralRepository implements OrderInterface
{
    public function __construct(
        protected OrderItemInterface $orderItemRepository
    ) {
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
                'user_id' => auth('api')->user()->id,
                'status' => OrderStatus::PENDING,
                'total_amount' => 0
            ]);

            if (isset($input['items'])) {
                $this->orderItemRepository->add($order->id, $input['items']);
            }

            return $order->fresh(['user', 'items.product']);
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


    /**
     * @param array $input
     * @return bool
     */
    public function update(int $id, array $input): bool
    {
        $data = $this->model->find($id);
            if (
                isset($input['status']) &&
                $input['status'] === OrderStatus::CANCELLED->value &&
                $data->payments()->exists()
            ) {
                return false;
            }

        return $data->update($input);
    }

    /**
     * @return bool
     */
    public function destroy(int $id): bool
    {
        if ($this->model->payments()->exists()) {
            return false;
        }
        $this->model->items()->delete();
        return parent::destroy($id);
    }

}
