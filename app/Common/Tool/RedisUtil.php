<?php


namespace App\Common\Tool;

class RedisUtil extends HyberfRedis
{
    //创建静态私有的变量保存该类对象
    static private $instance;
    private $redisHost = '';
    private $redisPort = '';
    private $redisPwd = '';
    private $redis = '';


    //防止使用clone克隆对象
    private function __clone(){}


    static public function getInstance()
    {
        //判断$instance是否是Singleton的对象，不是则创建
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }


//    function __construct($host = '139.196.141.25', $port = 6379, $pwd = '123456')

    function __construct($host = '39.101.193.254', $port = 6380, $pwd = '123456')
//    function __construct($host = '127.0.0.1', $port = 6379, $pwd = '')

    {

        parent::__construct();
        $this->redis = $this->hyredis;
//        $this->redis = new \Redis();
//        $this->redisHost = $host;
//        $this->redisPort = $port;
//        $this->redis->connect($this->redisHost, $this->redisPort);

    }

    function getKeys($key)
    {
        $flag = $this->redis->exists($key);

        $result = '';
        if ($flag) {
            $result = $this->redis->get($key);
        }

        return $result;
    }

    function setKeys($key, $value, $expTime = '')
    {
        $this->redis->set($key, $value);
    }

    function setKeyt($key, $value, $expTime = '')
    {
        $this->redis->set($key, $value, $expTime);
    }

    function setincrpay($key)
    {
       return $this->redis->incr($key);

    }


    function existskey($key)
    {
        return $this->redis->exists($key);
    }

    function expirepaytime($key, $time)
    {
        $this->redis->expire($key, $time);
    }

    function closeRedis()
    {
        $this->redis->close();
    }

    function deleteKey($key)
    {
        if ($this->redis->exists($key)) {
            $this->redis->del($key);
        }
    }

    function incrExpire($key, $time)
    {
        $flag = $this->redis->exists($key);
        if (empty($flag)) {
            $this->redis->incr($key);
            $this->redis->expire($key, $time);

            return;
        }
//			echo $this->redis->ttl($key);


        $this->redis->incr($key);
    }

    function setAdd($key, $val)
    {
//			$this->redis->delete($key);

        return $this->redis->sAdd($key, $val);
    }

    function setIsMember($key, $v)
    {
        return $this->redis->sIsMember($key, $v);
    }


    function members($key){
      return   $this->redis->sMembers($key);
    }
    function del_member($key,$tk){
        return   $this->redis->sRem($key,$tk);
    }
    function listPush($key, $v)
    {
        return $this->redis->lPush($key, $v);
    }

    function rlistPush($key, $v)
    {
        return $this->redis->rPush($key, $v);

    }
    function listpop($key){
        return $this->redis->lPop($key);
    }
    function rlistpop($key){
        return $this->redis->rPop($key);
    }
    function hashSet($key,$filed,$val){
        return $this->redis->hSet($key,$filed,$val);
    }
    function hashGet($key,$field){
        return $this->redis->hGet($key,$field);

    }
    function hashDel($key,$field){
        return $this->redis->hDel($key,$field);

    }
function setNxClose($key,$val,$time){
      return  $this->redis->set($key,$val,['nx','ex'=>$time]);

}
    function __destruct()
    {
        $this->redis->close();
    }

}

?>
