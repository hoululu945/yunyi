<?php

//declare(strict_types=1);

namespace App\Controller;
use App\Model\Member;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

/**
 * @AutoController(prefix="/api/door")
 */
class DoorController
{
    // Hyperf 会自动为此方法生成一个 /index/index 的路由，允许通过 GET 或 POST 方式请求
    public function index(RequestInterface $request)
    {
        // 从请求中获得 id 参数
        $id = $request->input('id', 1);
        return (string)$id;
    }
    public function indexs(RequestInterface $request)
    {
        // 从请求中获得 id 参数
        $id = $request->input('id', 1);
        return "sad";
    }

    function getk(ResponseInterface $response){
        try {
            $list = Db::select('SELECT * FROM `no3_member`;');
            return $response->json($list);
        }catch (\Exception $e){
            var_dump($e->getMessage());
//            print_r($list);
//            var_dump(get_class($throwable), $throwable->getMessage());

        }

    }

    function member(ResponseInterface $response){
        $info = Member::info();
        return $response->json($info);

    }

    function red(){
//        $rds = new \Redis();
//        try {
//            $ret = $rds->pconnect("127.0.0.1", 6390);
//            if ($ret == false) {
//                echo "Connect return false";
//                exit;
//            }
//            //设置超时时间为 0.1ms
//            $rds->setOption(3,0.0001);
//            $rds->get("aa");
//        } catch (\Exception $e) {
//            var_dump ($e);
//        }

    }



}