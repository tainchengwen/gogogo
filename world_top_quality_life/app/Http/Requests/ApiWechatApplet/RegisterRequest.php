<?php

namespace App\Http\Requests\ApiWechatApplet;

class RegisterRequest extends BaseRequest
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
            'code' => 'required|integer|',
            'mobile' => [
                'unique:users,mobile',
                'regex:/^1[3456789]\d{9}$/'
            ],
            'password' => [
                'regex:/^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9#!,.@$%&]{6,18}$/'
            ],
        ];
    }

    public function messages()
    {
        return [
            'code.*'=>'验证码格式错误',
            'mobile.regex'=> '请输入正确的手机号',
            'mobile.unique'=>'手机号已被注册',
            'password.regex'  => '密码应由数字和字母组成，长度为6-18位',
        ];
    }
}
