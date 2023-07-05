<?php


namespace App\Amqp\Consumer;
use App\Model\OptionLog;
use App\Model\UserCompany;
use App\Model\UserOrder;
use App\Model\UserOrderFile;
use App\Model\UserOrderGroup;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\DbConnection\Db;
use PhpAmqpLib\Message\AMQPMessage;

//
/**
 * @Consumer(exchange="yunyi", routingKey="order", queue="order", name ="DemoConsumer", nums=1)
 */

class OrderConsumer extends ConsumerMessage
{
    protected $type = Type::DIRECT;
    public  function consumeMessage($data, AMQPMessage $message): string
    {

        echo '@**********************************orderorderorderorder******************************************************************';
        $order_data = $data['order_data'];
        $group_data = $data['group_data'];
        $file_data_arr = $data['file_data_arr'];
        try {
            Db::beginTransaction();
            $order_id = UserOrder::query()->insertGetId($order_data);
            foreach ($group_data as $v ){
                $v['order_id'] = $order_id;
                $group_id = UserOrderGroup::insertGetId($v);

            }

            foreach ($file_data_arr as $file_v ){
                $file_v['order_id'] = $order_id;
                UserOrderFile::insertGetId($file_v);
            }

//            $group_data['order_id'] = $order_id;
//            $group_id = UserOrderGroup::insertGetId($group_data);
            if (empty($order_id) || empty($group_id)) {
                throw new \Exception('创建失败');
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
            Db::rollBack();
        }

        Db::commit();
        return Result::ACK;
    }
    function addFile($order_id){
//        $data['name'] = empty($fv['name']) ? "无" : $fv['name'];
//
//        $data['user_name'] = $fv['user_name'];
//        $company_id = $fv['company_id'];
//        $company_id = empty($company_id) ? 0 : $company_id;
//        $data['company_id'] = $company_id;
//        $data['company_name'] = empty($company_id) ? '云衣公社' : UserCompany::where('id', $company_id)->value('company_name');
//        $data['file_url'] = $fv['url'];
//        $data['upload_date'] = date("Y-m-d H:i:s");
////                    $data['type'] = $fv['user_type'];
//        $data['user_id'] = $fv['user_id'];
//        $data['file_type'] = $file_type;
//        $data['position_id'] = $relate_info['position_id'];
//        $data['department_id'] = $relate_info['department_id'];
//        $data['position_name'] = $relate_info['position_name'];
//        $data['department_name'] = $relate_info['department_name'];
//        $data['version_num'] = $version_data['version_num'];

//        $file_res = UserOrderFile::insertGetId($data);

    }
}