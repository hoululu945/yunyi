<?php


namespace App\Service;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\common\server\RedisUtil;

class AliSmsUtilServer
{
    /**
     * 发送短信验证码
     * @param $phone：手机号码
     * @param $code：验证码
     */
    public static function sendSmsCode($phone)
    {
        $code = mt_rand(1000,9999);

        $config = config('alisms');
//        var_dump($config);exit;
        $templateParam = json_encode(['code'=>$code]);

        try {
            AlibabaCloud::accessKeyClient($config['AccessKeyId'], $config['AccessKeySecret'])
                ->regionId($config['regionId']) // replace regionId as you need
                ->asDefaultClient();

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $phone,
                        'SignName' => $config['SignName'],
                        'TemplateCode' => $config['TemplateCode'],
                        'TemplateParam' => $templateParam,
                        'RegionId' => $config['regionId'],
                    ],
                ])
                ->request();
            $redis = \App\Common\Tool\RedisUtil::getInstance();
            $redis->setKeyt($phone.'_code',$code,300);
            return $result->toArray();
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }
    }
}