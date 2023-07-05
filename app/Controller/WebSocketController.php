<?php


namespace App\Controller;

use App\Amqp\Producer\TalkRecordProducer;
use App\Common\Tool\RedisUtil;
use App\Model\UserOrderTalk;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\WebSocketServer\Context;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use Hyperf\Amqp\Producer;
use Hyperf\Di\Annotation\Inject;


class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    protected $roleService;
    /**
     * @Inject()
     * @var Producer
     */
    private $producer;

    public function onMessage($server, Frame $frame): void
    {

        echo '********1**********';
        echo $frame->data;
        echo '********2*********';

//        $frame->data = mb_convert_encoding($frame->data,"UTF-8");
        $data = json_decode($frame->data, true);
        var_dump($data);
        echo '********3*********';

        $redis = RedisUtil::getInstance();
        if (!empty($data['order_id']) && !empty($data['user_id']) && !empty($data['message'])) {
            Context::set("order_id", $data['order_id']);

            $data_record['order_id'] = $data['order_id'];
            $data_record['user_id'] = $data['user_id'];
            $data_record['type'] = empty($data['type']) ? 1 : $data['type'];
            $data_record['content'] = $data['message'];
            $data_record['creation_time'] = date("Y-m-d H:i:s");
            $data_record['headimage'] = empty($data['headimage'])?'http://www.yybxk.net/static/img/default.7475f80.jpeg':$data['headimage'];
            $data_record['user_name'] = empty($data['user_name'])?'潜水的小孩':$data['user_name'];
            $data_record['name'] = empty($data['name'])?'潜水的小孩':$data['name'];
//            $this->producer->produce(new OptionLogProducer($date));
            $this->producer->produce(new TalkRecordProducer($data_record));
//            UserOrderTalk::insertGetId($data_record);
//            $redis->setAdd('message',$frame->fd);
            echo $frame->fd . '^^^^^^^^^^^^^^^^^^^^^^^^^';
            $key = $data['order_id'] . '_message';
            $redis->setAdd($key, $frame->fd);
            Context::set('room_no', $key);
            $fdList = $redis->members($key);
//            if (in_array($frame->fd, $fdList)) {
            $redis->expirepaytime($key, 7200);
//            }
//
//            $msg = json_encode([
//                'fd' => $frame->fd,//客户id
//                'msg' => $frame->data,//发送数据
//                'total_num' => count($fdList)//聊天总人数
//            ], JSON_UNESCAPED_UNICODE);

//            $msg = json_encode([
//                'fd' => $frame->fd,//客户id
//                'msg' => $frame->data,//发送数据
//                'total_num' => count($fdList)//聊天总人数
//            ], JSON_UNESCAPED_UNICODE);
            //发送消息
            foreach ($fdList as $fdId) {
                $msg = json_encode([
                    'fd' => $fdId,//客户id
                    'msg' => $frame->data,//发送数据
                    'total_num' => count($fdList)//聊天总人数
                ], JSON_UNESCAPED_UNICODE);
                $is_has = $redis->members('message', $frame->fd);
                if ($is_has) {
                    $redis->expirepaytime('message', 7200);
                    echo 'success*******'.$fdId;
                    $fdid = Context::get('fdid');
                    echo 'current******'.$fdid;
                    $is_single = $redis->setAdd('single_push',$fdId);
                    if($is_single){
                        var_dump($msg);
                        $server->push($fdId, $msg);
                    }

                }
            }
            $redis->deleteKey('single_push');


        } else {
            if(!empty($data['order_id'])){
                $key = $data['order_id'].'_message';
                $redis->setAdd($key, $frame->fd);
                Context::set('room_no', $key);
//            if (in_array($frame->fd, $fdList)) {
                $redis->expirepaytime($key, 7200);
            }else{

                if($frame->data=='wechat_login'){
                    echo '上天遁地，九天揽月1';

                    $token_arr = $redis->getKeys('code_login_fdId_'.$frame->fd);
                    if(!empty($token_arr)){
                        $msg = json_encode([
                            'fdId' => $frame->fd,//客户id
                            'token_arr'=>unserialize($token_arr),
                            'type'=>'login'
                        ], JSON_UNESCAPED_UNICODE);
                        $server->push($frame->fd, $msg);
                    }else{
                        $server->push($frame->fd, $frame->data);
                    }
                }else{
                    echo '上天遁地，九天揽月3';

                    $server->push($frame->fd, $frame->data);
                }

//                echo '上天遁地，九天揽月2';

//                $server->push($frame->fd, $frame->data);

            }


        }
//        var_dump($data);
//        echo 55555;
//        $server->push($frame->fd,$frame->data);
    }

    public function onClose($server, int $fd, int $reactorId): void
    {

        //删掉客户端id
        $redis = RedisUtil::getInstance();
        $is_has = $redis->members('message', $fd);
        $room_no = Context::get('room_no');
        $order_id = Context::get('order_id');

        echo '#####close' . $fd.'^^^'.$room_no.'order_id'.$order_id;
        $redis->del_member($room_no, $fd);

        if ($is_has) {
            echo '#####';
            $redis->del_member('message', $fd);
        }

//        $key = $data['order_id'] . '_message';
        //移除集合中指定的value
//        $redis->sRem('websocket_sjd_1', $fd);
//        var_dump('closed');


        var_dump('closed');
    }

    public function onOpen($server, Request $request): void
    {

        Context::set("name", 'hll');

        //保存客户端id
        $redis = RedisUtil::getInstance();
        $redis->setAdd('message', $request->fd);
        Context::set("fdid", $request->fd);

        $redis->expirepaytime('message', 7200);
        echo 'open$$$' . $request->fd;

        $msg = json_encode([
            'fdId' => $request->fd,//客户id
            'type'=>'storage'
        ], JSON_UNESCAPED_UNICODE);
//        $server->push($request->fd, 'Opened'.$request->fd);
        $server->push($request->fd, $msg);

    }

}
