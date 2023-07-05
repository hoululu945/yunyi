<?php


namespace App\Common\Tool;

use Hyperf\Utils\ApplicationContext;


class HyberfRedis
{
    public $hyredis;
    public $result;
    function __construct()
    {
        $container = ApplicationContext::getContainer();

        $this->hyredis = $container->get(\Hyperf\Redis\Redis::class);
//        $this->result = $redis->keys('*');

//        return $result;
    }

}