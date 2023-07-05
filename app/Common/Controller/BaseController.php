<?php


namespace App\Common\Controller;


use App\Common\Tool\RedisUtil;
use App\Controller\AbstractController;
use App\Model\CompanyUserRelate;
use App\Model\OptionLog;
use App\Model\UserCompany;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

class BaseController extends AbstractController
{

    protected $a = 1;
    protected $response;
    protected $switch_company;

    function __construct(ResponseInterface $response)
    {
        parent::__construct();
        $this->response = $response;

    }

    function show($status, $message, $back_data = [])
    {
        return $this->response->json(['code' => $status, 'message' => $message, 'data' => $back_data]);

    }

    function success($message = '请求处理成功', $back_data = [])
    {
        return $this->response->json(['code' => 200, 'message' => $message, 'data' => $back_data]);

    }

    function error($message = '请求处理失败', $back_data = [])
    {
        return $this->response->json(['code' => -1, 'message' => $message, 'data' => $back_data]);

    }

    function get_ordersn()
    {

        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $orderSn = $yCode[intval(date('Y')) - 2021] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $orderSn;
    }

    function get_ordersn_big()
    {
        $redis = RedisUtil::getInstance();
        $sn_id = $redis->setincrpay('sn_id');
        $date = date("YmdHis");
        $orderSn = $date . $sn_id;
//        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
//        $orderSn = $yCode[intval(date('Y')) - 2021] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $orderSn;
    }
}