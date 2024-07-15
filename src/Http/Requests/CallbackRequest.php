<?php

namespace Botble\Payway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tran_id' => 'required',
            'apv' => 'required',
            'status' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        // Check if the request method is POST
        $validator->after(function ($validator) {
            if ($this->method() !== 'POST') {
                $validator->errors()->add('method', 'The request method must be POST.');
            }
        });
    }
}
