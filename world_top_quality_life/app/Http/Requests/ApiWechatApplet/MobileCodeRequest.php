<?php

namespace App\Http\Requests\ApiWechatApplet;

class MobileCodeRequest extends BaseRequest
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
            'type' => 'required|integer|min:0|max:2',

        ];
    }

    public function messages()
    {
        return [
            'mobile.regex'=> '请输入正确的手机号',
            'type.*' => '验证码类型错误',
        ];
    }
}
