<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'gateway' => [
                'id' => $this->gateway->id,
                'name' => $this->gateway->name
            ],
            'gateway_response' => $this->gateway_response,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order' => $this->when($this->relationLoaded('order'), function () {
                return [
                    'id' => $this->order->id,
                    'status' => $this->order->status,
                    'total_amount' => $this->order->total_amount
                ];
            })
        ];
    }
}
