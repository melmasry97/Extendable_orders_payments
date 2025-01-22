<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string', 'exists:payment_gateways,name,is_active,1'],
            'payment_method' => ['required', 'string'],
            'card_number' => ['required_if:payment_method,card', 'string'],
            'expiry_month' => ['required_if:payment_method,card', 'string'],
            'expiry_year' => ['required_if:payment_method,card', 'string'],
            'cvv' => ['required_if:payment_method,card', 'string'],
            'email' => ['required_if:payment_method,paypal', 'email'],
            'return_url' => ['sometimes', 'url'],
            'cancel_url' => ['sometimes', 'url'],
        ];
    }
}
