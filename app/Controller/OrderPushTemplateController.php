<?php
/**
 * @Author: houlu
 * @Date: 2022/07/03/下午5:13
 * @Description:
 */

namespace App\Controller;


use App\Common\Controller\BaseController;
use App\Common\Tool\RedisUtil;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * Class OrderPushTemplateController
 * @package App\Controller
 * @AutoController(prefix="/api/push")
 */
class OrderPushTemplateController  extends BaseController
{



    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;


    function __construct()
    {

        parent::__construct($this->response);
        $this->appid = config('WX_APPID');
        $this->secret = config('WX_SECRET');


    }




    function pushTemp($data,$type,$openid_arr)
    {
        foreach ($openid_arr as $open_id){
            $tem = '';
            switch ($type){
                case 1:
                    $tem = ' {
                   "touser":"'.$open_id.'",
                   "template_id":"FUSQlZKVBDCCrkenrXzSAVBWD0NA4u4co0Fe_BJCIcM",
                   "data":{
                           "first": {
                               "value":" 您收到了一条新的订单。",
                               "color":"#173177"
                           },
                           "keyword1":{
                               "value":"' . $data['creation_time'] . '",
                               "color":"#173177"
                           },
                           "keyword2": {
                               "value":"'.$data['parameter'].'",
                               "color":"#173177"
                           },
                           "keyword3": {
                               "value":"'.$data['user_name'].'",
                               "color":"#173177"
                           },
                           "remark":{
                               "value":"订单详情：'.$data['title'].'",
                               "color":"#FF0000"
                           }
                   }
               }';
                    break;
                case 2:
                    $tem = ' {
                   "touser":"'.$open_id.'",
                   "template_id":"ELxPccAka81Re0h4Ghit9nTOg3zu0ZRRuSFwdqC_VZA",
                   "data":{
                       
                           "keyword1":{
                               "value":"提交申请成功通知 ",
                               "color":"#173177"
                           },
                           "keyword2": {
                               "value":"提交成功 ",
                               "color":"#173177"
                           },
                           "keyword3": {
                               "value":"' . date("Y-m-d H:i:s") . '",
                               "color":"#173177"
                           }
                         
                   }
               }';
                    break;
                case 3:
                    $tem = ' {
                   "touser":"'.$open_id.'",
                   "template_id":"NQazysFT5SpJRV3ZOc9CHcN11CDaXCtZdBGN0BzsLgo",
                   "data":{
                         "first": {
                               "value":" 您提交的订单已经处理完成 。",
                               "color":"#173177"
                           },
                           "keyword1":{
                               "value":"'.$data['order_no'].'",
                               "color":"#173177"
                           },
                           "keyword2": {
                               "value":"已完成 ",
                               "color":"#173177"
                           },
                           "keyword3": {
                               "value":"' . date("Y-m-d H:i:s") . '",
                               "color":"#173177"
                           },
                              "remark":{
                               "value":"感谢您一直以来的支持，请您登陆系统查询完成结果。",
                               "color":"#FF0000"
                           }
                         
                   }
               }';
                    break;

            }

            echo "-------------------------";
            $token = $this->get_online_token();

            echo  "*********------------------*************";
//        echo $token;
//        $token = '35_HH5pAAO1TR-L-26e72y9k809R9iru-gpHK5Abr8XjenPaNbkvP4ia_wtmNsJINU5ItIg89-S92UVlWGtAjBaQOg6V8X0Vuf8pzSn8_iopJgabjV-G0-TBY0n__wkvqNmCkkrcs_2rLju2ufYPRScACASUV';
            $pub_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $token;
            $data = $this->http_post($pub_url, $tem);
            var_dump($data);
            print_r($tem . $data . "**####################*", true);
        }


    }
    function http_post($url, $data_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-AjaxPro-Method:ShowList',
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function get_online_token()
    {
        $redis = RedisUtil::getInstance();
        $appId = $this->appid;
        $appSecret = $this->secret;
        $token_name = 'accessToken';
        $accessToken = $redis->getKeys($token_name);
//        if (empty($accessToken)){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appId . "&secret=" . $appSecret;
            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $url); //要访问的地址
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过证书验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
            $data = json_decode(curl_exec($ch), true);
//        $redis = new \RedisUtil();
            $accessToken = $data['access_token'];
            $redis->setKeys($token_name, $data['access_token'], 2 * 60 * 60);
//        }

//        $redis->closeRedis();
        return $accessToken;

    }


}
