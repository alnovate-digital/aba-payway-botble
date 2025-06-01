<?php

namespace Alnovate\Payway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tran_id' => 'required|string',
            'apv' => 'required|string',
            'status' => 'required|string',
            'order_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'customer_type' => 'required|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Optional: enforce method check only if needed
            if (! in_array($this->method(), ['GET', 'POST'])) {
                $validator->errors()->add('method', 'The request method must be GET or POST.');
            }
        });

        return $validator;
    }
}

