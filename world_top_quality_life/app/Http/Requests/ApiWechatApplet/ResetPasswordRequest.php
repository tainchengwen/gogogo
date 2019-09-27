<?php

namespace App\Http\Requests\ApiWechatApplet;

class ResetPasswordRequest extends BaseRequest
{
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mobile' => [
                'regex:/^1[3456789]\d{9}$/'
            ],
            'code'=>'required|integer|min:100000|max:999999'

        ];
    }

    public function messages()
    {
        return [
            'mobile.regex'=> '请输入正确的手机号',
            'code.*' => '验证码错误',
        ];
    }
}
