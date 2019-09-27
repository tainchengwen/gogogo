<?php

namespace App\Http\Requests\ApiWechatApplet;

use App\Http\Controllers\ApiWechatApplet\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{
    use ApiResponse;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $error_msg= $validator->errors()->first();
        throw new HttpResponseException($this->error($error_msg));
    }
}
