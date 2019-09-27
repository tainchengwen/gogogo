<?php

namespace App\Http\Requests\ApiWechatApplet;

class LoginRequest extends BaseRequest
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
            'type' => 'required|integer|min:0|max:1',
            'mobile' => [
                'required_if:type,0,1',
                'regex:/^1[3456789]\d{9}$/'
            ],
            'code'=>'required_if:type,1|integer|min:100000|max:999999',
            'password' => [
                'required_if:type,0',
                'regex:/^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9#!,.@$%&]{6,18}$/'
            ],
        ];
    }

    public function messages()
    {
        return [
            'type.*'=>'登录类型错误',
            'mobile.*'=> '请输入正确的手机号',
            'code.*' => '验证码错误',
            'password.*'  => '密码应由数字和字母组成，长度为6-18位',
        ];
    }
}
