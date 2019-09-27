<?php

namespace App\Http\Controllers\ApiWechatApplet\Traits;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Illuminate\Support\Facades\Log;

trait SendSms
{
    public function sendAliSms($mobile, array $param, $sign='联傲贸易', $tmp_id = 'SMS_174170576')
    {
        AlibabaCloud::accessKeyClient('LTAI6a4unMugDL4S', 'qG1XdYWiFr3EHl9BvzygS2vi170JMO')
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $mobile,
                        'SignName' => $sign,
                        'TemplateCode' => $tmp_id,
                        'TemplateParam' => json_encode($param),
                    ],
                ])
                ->request();
                return $result->toArray();
        } catch (ClientException $e) {
            Log::error($e->getErrorMessage() . PHP_EOL);
        } catch (ServerException $e) {
            Log::error($e->getErrorMessage() . PHP_EOL);
        }
        return false;
    }
}