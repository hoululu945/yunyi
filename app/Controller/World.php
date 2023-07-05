<?php
declare(strict_types=1);


namespace App\Controller;
use App\Common\Controller\BaseController;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

use App\Exception\FooException;

use Hyperf\Logger\LoggerFactory;

///**
// * user
// * $user
// * @AutoController()
// * @package App\Controller
// *
//
// */

/**
 * @Controller()
 */


class World extends BaseController
{
    public function __construct(LoggerFactory $loggerFactory)
    {
        parent::__construct();
//        echo 231231231;exit;
        // 第一个参数对应日志的 name, 第二个参数对应 config/autoload/logger.php 内的 key
    }
    public function indexs(RequestInterface $request,LoggerFactory $loggerFactory)
    {


//        $this->logger = $loggerFactory->get('logs', 'default');
//
//        // 从请求中获得 id 参数
//        $this->logger->info("Your log message.");
//
//        $id = $request->input('id', 1);
//        $arr = [1,2,3];
//        var_dump($arr);
//        print_r($arr);
        return $this->a;
        return "sadsssffff2222";
    }

//    /**
//     * @GetMapping(path="/user/{id:/d+}")
//     */
    /**
     * @RequestMapping(path="index", methods="get,post")
     */
    function index_2(RequestInterface $request){

//        throw new FooException('Foo Exception...', 800);
        try {
            $a = [];
            var_dump($a[1]);
        } catch (\Throwable $throwable) {
            var_dump(get_class($throwable), $throwable->getMessage());
        }
        $url = $request->url();

// 带上查询参数
        $url1 = $request->fullUrl();
        return $url.$url1;
    }

    function getIp()
    {
//        if ($_SERVER["HTTP_CLIENT_IP"] && strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")) {
//            $ip = $_SERVER["HTTP_CLIENT_IP"];
//        } else {
//            if ($_SERVER["HTTP_X_FORWARDED_FOR"] && strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")) {
//                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
//            } else {
//                if ($_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
//                    $ip = $_SERVER["REMOTE_ADDR"];
//                } else {
//                    if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'],
//                            "unknown")
//                    ) {
//                        $ip = $_SERVER['REMOTE_ADDR'];
//                    } else {
//                        $ip = "unknown";
//                    }
//                }
//            }
//        }

            $ip = null;
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim(current($ip));
            }
            return $ip;

    }

    function get_client_ip()
    {
        $ip = null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim(current($ip));
        }
        return $ip;
    }

}