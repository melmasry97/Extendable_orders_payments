<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::enum(OrderStatus::class)],

            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.name' => ['required_with:items', 'string'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],

            'customer_details' => ['sometimes', 'required', 'array'],
            'customer_details.name' => ['required_with:customer_details', 'string'],
            'customer_details.email' => ['required_with:customer_details', 'email'],
            'customer_details.phone' => ['required_with:customer_details', 'string'],
            'customer_details.address' => ['required_with:customer_details', 'string'],

            'notes' => ['nullable', 'string']
        ];
    }
}
