<?php

namespace App\Http\Requests\OrderItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'sometimes|required|integer|min:1',
        ];
    }
}
