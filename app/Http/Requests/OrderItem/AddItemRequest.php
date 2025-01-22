<?php

namespace App\Http\Requests\OrderItem;

use Illuminate\Foundation\Http\FormRequest;

class AddItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
