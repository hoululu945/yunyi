<?php


namespace App\Controller;


use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Amqp\Producer\DemoProducer;
use App\Amqp\Producer\OrderExportProducer;
use App\Amqp\Producer\OrderProducer;
use App\Common\Controller\BaseController;
use App\Common\Tool\ExcelBom;
use App\Common\Tool\HyberfRedis;
use App\Common\Tool\ImgeDown;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use App\Model\BandDui;
use App\Model\BandRecord;
use App\Model\CompanyUserRelate;
use App\Model\DesignWriteDetail;
use App\Model\OrderDetail;
use App\Model\OrderFieldVersion;
use App\Model\OrderSendNum;
use App\Model\OrderVersion;
use App\Model\PlatemakingWriteDetail;
use App\Model\StyleParam;
use App\Model\SumBand;
use App\Model\User;
use App\Model\UserCompany;
use App\Model\UserOrder;
use App\Model\UserOrderDiscuss;
use App\Model\UserOrderFile;
use App\Model\UserOrderGroup;
use App\Model\UserOrderInfo;
use App\Model\UserOrderTalk;
use App\Service\RoleService;
use App\Service\UserAuthService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Service\MenuService;
use Hyperf\Config\Config;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use App\Middleware\Auth\CheckToken;
use App\Amqp\Producer\OptionLogProducer;
use Hyperf\Amqp\Producer;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

//

/**
 * Class MenuController
 * @package App\Controller
 * @AutoController(prefix="/api/order")
 * @Middlewares({
 *     @Middleware(CheckToken::class)
 * })
 */
class OrderController extends BaseController
{

    /**
     * @Inject
     * @var RoleService
     */
    protected $roleService;


    //1nan 2女 3儿童
    function styleList(RequestInterface $request)
    {
        $param = $request->all();
        if (empty($param['style_type'])) {
            return $this->error('参数缺失');
        }
        $style_list = StyleParam::query()->where('sex', $param['style_type'])->pluck('style');
        return $this->success('返回成功', $style_list);


    }

    function create($data){
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
//            echo $e->getMessage();
            Db::rollBack();
            return false;
        }

        Db::commit();
        return $order_id;

    }

    function createOrder(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;
        $user_id = $user_info['id'];
        $company_id = $this->request->switch_company;
        $redis = RedisUtil::getInstance();
        try {
            $res = $redis->setNxClose('create_' . $user_info['id'], 'av', 30);
            if (!$res) {
                throw new \Exception('有订单正在创建请稍后等待');
            }
            Db::beginTransaction();
            $data['order_no'] = $this->get_ordersn_big();
            if (empty($param['title']) || !isset($param['is_sample_dress']) || empty($param['style']) || empty($param['images']) || empty($param['require'])) {
                throw new \Exception('参数缺失');
            }
            $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first();
            if (empty($relate_info)) {
                throw new Exception('用户信息缺失');
            }
            $is_admin = $relate_info->isAdministrator;

            $data['title'] = $param['title'];
            $data['user_id'] = $user_id;
            $data['company_id'] = $company_id;
            $data['sample'] = $param['is_sample_dress'];
            $data['claims'] = $param['require'];
            $data['images'] = implode(',', $param['images']);
            $data['parameter'] = $param['style'];
            $data['company_name'] = $relate_info->company_name;
            $data['creation_time'] = date('Y-m-d H:i:s');
            $data['need_type'] = empty($param['need_type'])?0:1;
            $group_data['user_id'] = $user_id;
            $group_data['creation_time'] = date("Y-m-d H:i:s");
            $group_data['company_id'] = $company_id;
            $group_data['invite_status'] = 2;
            $group_data['user_name'] = $user_info['userName'];
            $group_data['company_name'] = $relate_info->company_name;
            $group_data['order_no'] = $data['order_no'];
            $group_data['position_id'] = $relate_info->position_id;
            $group_data['position_name'] = $relate_info->position_name;
            $group_data['department_id'] = $relate_info->department_id;
            $group_data['department_name'] = $relate_info->department_name;
            $data['user_name'] = $user_info['userName'];
            $queue_data['order_data'] = $data;
            $queue_data['group_data'][] = $group_data;

            if (empty($is_admin)) {
                $admin_relate_info = CompanyUserRelate::where(['isAdministrator' => 1, 'company_id' => $company_id])->first();
                if (!empty($admin_relate_info)) {
                    $group_data1['user_id'] = $admin_relate_info->user_id;
                    $group_data1['creation_time'] = date("Y-m-d H:i:s");
                    $group_data1['company_id'] = $company_id;
                    $group_data1['invite_status'] = 2;
                    $group_data1['user_name'] = $admin_relate_info->user_name;
                    $group_data1['company_name'] = $relate_info->company_name;
                    $group_data1['order_no'] = $data['order_no'];
                    $group_data1['position_id'] = $admin_relate_info->position_id;
                    $group_data1['position_name'] = $admin_relate_info->position_name;
                    $group_data1['department_id'] = $admin_relate_info->department_id;
                    $group_data1['department_name'] = $admin_relate_info->department_name;
                    $queue_data['group_data'][] = $group_data1;
                }


            }
//            $file_data['order_id'] =
            $file_data = [];
            $file_data_arr = [];
            foreach ($param['images'] as $fk => $fv) {
                $implode_url = explode('.',$fv);
                $len = count($implode_url);
//                $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first()->toArray();
                $fname = $implode_url[$len-2];
                $fname_arr = explode('/',$fname);
                $len_f = count($fname_arr);
                $file_data['name'] = $fname_arr[$len_f-1];

                $file_data['user_name'] = $user_info['userName'];

                $file_data['company_id'] = $company_id;
                $file_data['company_name'] = empty($company_id) ? '云衣公社' : UserCompany::where('id', $company_id)->value('company_name');
                $file_data['file_url'] = $fv;
                $file_data['upload_date'] = date("Y-m-d H:i:s");
//                    $data['type'] = $fv['user_type'];
                $file_data['user_id'] = $user_info['id'];
                $file_data['file_type'] = 2;
                $file_data['position_id'] = $relate_info['position_id'];
                $file_data['department_id'] = $relate_info['department_id'];
                $file_data['position_name'] = $relate_info['position_name'];
                $file_data['department_name'] = $relate_info['department_name'];
//                $data['version_num'] = $version_data['version_num'];

//                $file_res = UserOrderFile::insertGetId($data);
                $file_data_arr[] = $file_data;

            }
//            var_dump($file_data);
//            var_dump($f);

//        var_dump($queue_data);
            $queue_data['file_data_arr'] = $file_data_arr;
            var_dump($queue_data);
//            $this->producer->produce(new OrderProducer($queue_data));
            $order_id = $this->create($queue_data);
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
//        try {
//            Db::beginTransaction();
//            $order_id = UserOrder::query()->insertGetId($data);
//            $group_data['order_id'] = $order_id;
//            $group_id = UserOrderGroup::insertGetId($group_data);
//            if (empty($order_id) || empty($group_id)) {
//                throw new Exception('创建失败');
//            }
//
//        } catch (\Exception $e) {
//            echo $e->getMessage();
//            Db::rollBack();
//        }
//
//        Db::commit();
        $redis->deleteKey('create_' . $user_info['id']);
        $is_tan = false;
        if(empty($company_id)){
            $is_tan = true;
        }
        return $this->success('创建订单成功',['order_id'=>$order_id,'istan'=>$is_tan]);


    }

    function getIcon($order_info)
    {
        $Incon_arr['apply_over'] = ['is_show' => 0, 'name' => '申请结单'];
        $Incon_arr['roll_back'] = ['is_show' => 0, 'name' => '打回'];
        $Incon_arr['finish'] = ['is_show' => 0, 'name' => '完结'];
        if (($order_info['apply_finish'] == 0 && $order_info['sure_finish'] == 0 && $order_info['is_submit_version'] == 1) && ($order_info['is_send'] == 0 || $order_info['type'] == 2)) {
            $Incon_arr['apply_over'] = ['is_show' => 1, 'name' => '申请结单'];


        }
        if (($order_info['sure_finish'] == 0 && $order_info['is_submit_version'] == 1 && $order_info['invite_status'] == 2) && ($order_info['type'] == 1 || $order_info['is_send'] == 0)) {
            $Incon_arr['roll_back'] = ['is_show' => 1, 'name' => '打回'];

        }
        if (($order_info['sure_finish'] == 0 && $order_info['apply_finish'] == 1) && ($order_info['type'] == 1 || $order_info['is_send'] == 0)) {
            $Incon_arr['finish'] = ['is_show' => 1, 'name' => '完结'];

        }
        $zip_path = BASE_PATH . '/storage/file/order_zip';

//        if (!file_exists($dir)) {
        $zip_name = $zip_path . '/' . $order_info['order_id'] . '_order.zip';

        $status = $this->iconStatus('api/order/down');
        if (!is_file($zip_name)) {
//            return $this->error('没有提交可下载的的版本，下载失败');
              $status = 0;
        }
        $Incon_arr['down'] = ['is_show' => $status, 'name' => '下载'];
        return $Incon_arr;


    }

    function iconStatus($rout_url)
    {

        $user_info = $this->request->user_info;
        $redis = RedisUtil::getInstance();
        $rule_all = $redis->getKeys('rule_all');
        $rule_all = json_decode($rule_all, true);

//            $rule_list = $redis->getKeys('rule_name_user' . $user_info['id']);
        $rule_list_hash = $redis->hashGet('user_rule', strval($user_info['id']));
        $rule_list_hash = unserialize($rule_list_hash);
        if (in_array($rout_url, $rule_all)) {

            if (!in_array($rout_url, $rule_list_hash)) {

                return 0;
            }
        }

        return 1;


    }

    /**
     * 订单列表
     */
    function adminOrderList($where, $param,$like_where=[])
    {
        $redis = RedisUtil::getInstance();
        unset($where['user_id']);
        unset($where['company_id']);
        $switch_company = $this->request->switch_company;
        $size = empty($param['size']) ? 10 : $param['size'];

        if (!empty($param['startTime']) && !empty($param['endTime'])) {
            $list = UserOrder::where($where)
                ->whereBetween('creation_time', [$param['startTime'], $param['endTime']])
                ->where($like_where)
                ->orderByDesc('creation_time')->paginate($size, ['*'], 'current');

        } else {
            $list = UserOrder::where($where)
                ->where($like_where)
                ->orderByDesc('creation_time')->paginate($size, ['*'], 'current');

        }
        echo 'timebegin1****^^^999999^'.time();

        $list = json_decode(json_encode($list), true);
//        var_dump($list['data'][0]['order']);
        foreach ($list['data'] as $key => $val) {
            $order = [];

            $order['order_id'] = $val['id'];
            $order['order_user_id'] = $val['user_id'];
            $order['title'] = $val['title'];
            $order['sample'] = $val['sample'];

            $order['claims'] = $val['claims'];

            $order['images'] = explode(',', $val['images']);
            $order['parameter'] = $val['parameter'];
            $order['order_no'] = $val['order_no'];
//            $order['order_user_name'] = User::where('id', $val['user_id'])->value('userName');
            $order['order_user_name'] = $val['user_name'];

//            $order['user_type'] = $val['user_type'];
//            $order['step'] = $val['step'];
            $order['current_company_id'] = $switch_company;
            $order['status'] = $val['status'];
            $order['creation_time'] = $val['creation_time'];
            $order['company_id'] = $val['company_id'];
            $order['pay_status'] = $val['pay_status'];
            $order['is_submit_version'] = $val['is_submit_version'];

            $order['total_price'] = $val['total_price'];
            $order['company_name'] = $val['company_name'];
            $order['apply_cancel'] = $val['apply_cancel'];
            $order['need_type'] = $val['need_type'];

//            $order['order_user_type'] = $val['user_type'];
            $order['invite_status'] = 2;
            $order['is_accept'] = $val['is_send'];

            $order['sure_finish'] = $val['sure_finish'];
            $order['type'] = 0;

            $order['apply_finish'] = $val['apply_finish'];

            $order['is_finish'] = $val['sure_finish'];
            $order['is_pay'] = $val['is_pay'];
            $order['is_send'] = $val['is_send'];
            $order['level'] = 0;

//            $order['down_url'] = \config('domain_url').'storage/file/order_zip/'.$val['id'].'_order.zip';
            $order['down_url'] = \config('domain_url').'api/down/downZip?order_id='.$val['id']."&time=".uniqid();

            $group_list = UserOrderGroup::where('order_id', $val['id'])->get()->toArray();

            $file_list = UserOrderFile::where('order_id', $val['id'])->get();
            echo 'timebegin1****^^^^%%%%$$$$'.time();

//            var_dump($file_list);
//            echo '%%%%%%%^^^^^^^^^^^^^^^^^^^^^^^^^';
            if (empty($file_list)) {
//                echo '$$$$$$$$$$$$$$';
                $order['user_uploade'] = [];
            } else {
//                echo '##################';
                $file_list = $file_list->toArray();
                if (empty($file_list)) {
                    $order['user_uploade'] = [];
                } else {
                    foreach ($file_list as $fk => $fv) {
                        $order['user_uploade'][] = $fv['user_name'] . '---上传了文件';
                    }

                }


            }

            $need = [];
            $service = [];
            foreach ($group_list as $k => $v) {
                $jiayi = [];
//                switch ($v['user_type']) {
//                    case 1;
//                        $job = '设计师';
//                        break;
//                    case 2;
//                        $job = '制版师';
//                        break;
//                    case 3;
//                        $job = '制样师';
//                        break;
//                }
                if ($v['type'] == 1) {
                    $need[] = $v['company_name'] . '--' . $v['position_name'] . '--' . $v['user_name'];
                } else {
                    $service[] = $v['company_name'] . '--' . $v['position_name'] . '--' . $v['user_name'];
                }

            }
            $order['need'] = $need;
            $order['service'] = $service;
//            $order['sample'] = $val['order']['sample'];
//            $order['sample'] = $val['order']['sample'];
//            $order['sample'] = $val['order']['sample'];
//            $order['sample'] = $val['order']['sample'];
            $version_data = OrderVersion::where('order_id', $val['id'])->get()->toArray();
            $version_list = [];
            $version_info = [];
            echo 'timebegin1****^^^^%%%%$$$$#$@#$#$%@@@'.time();

            foreach ($version_data as $k => $v) {
                $version_list[] = '版本：' . $v['version_num'] . '---' . $v['add_time'];
                $version_info[$k]['version_num'] = $v['version_num'];
                $version_info[$k]['version_name'] = '版本：' . $v['version_num'] . '---' . $v['add_time'];
            }
//            $order['version_info'] = $version_info;

            $order['version_list'] = $version_list;
            $icon = $this->getIcon($order);
            $order['icon'] = $icon;
            $list['data'][$key] = $order;


        }
        return $list;
    }

    function orderList(RequestInterface $request)
    {
        $param = $request->all();
        $redis = RedisUtil::getInstance();
        $size = empty($param['size']) ? 10 : $param['size'];
        $user_info = $request->user_info;
        $user_id = $request->user_info['id'];
        $company_id = $this->request->switch_company;
        $company_id = empty($company_id) ? 0 : $company_id;

        $where = ['user_id' => $user_id, 'company_id' => $company_id, 'status' => 0];
        $relate_info = CompanyUserRelate::where(['user_id' => $user_id, 'company_id' => $company_id])->first();
        $like_where = function ($query) use ($param){
            if (!empty($param['sn'])) {
              $query->where('order_no','like','%'.$param['sn'].'%');
            }
            if (!empty($param['title'])) {
                $query->where('title','like','%'.$param['title'].'%');
            }
            if (!empty($param['parameter'])) {
                $query->where('parameter','like','%'.$param['parameter'].'%');
            }
        };
//        if (!empty($param['sn'])) {
//            $where['order_no'] = $param['sn'];
//        }

        if (!empty($param['invite_status']) && in_array($param['invite_status'], [1, 2, 3])) {

            $where['invite_status'] = $param['invite_status'];
        }
        if (!empty($param['is_finish'])) {

            $where['is_finish'] = $param['is_finish'];

        }
        if (empty($relate_info)) {
            return $this->error('用户信息缺失');
        }
        if (!empty($user_info['is_admin'])) {
            if (isset($where['is_finish'])) {
                $where['sure_finish'] = $where['is_finish'];
                unset($where['is_finish']);
            }
            $list = $this->adminOrderList($where, $param,$like_where);
            return $this->success('返回成功', $list);
        }

        if (!empty($param['startTime']) && !empty($param['endTime'])) {
            $list = UserOrderGroup::with('order')
                ->whereHas("order",function ($q)use ($like_where){
                    $q->where($like_where);
                })
                ->whereBetween('creation_time', [$param['startTime'], $param['endTime']])
                ->where($where)
                ->orderByDesc('creation_time')->paginate($size, ['*'], 'current');

        } else {
            $list = UserOrderGroup::with('order')
                ->where($where)
                ->whereHas("order",function ($q)use ($like_where){
                    $q->where($like_where);
                })
                ->orderByDesc('creation_time')->paginate($size, ['*'], 'current');

        }
        $list = json_decode(json_encode($list), true);
//        var_dump($list['data'][0]['order']);
        foreach ($list['data'] as $key => $val) {
            $order = [];
            $order['order_id'] = $val['order']['id'];
            $order['order_user_id'] = $val['order']['user_id'];
            $order['title'] = $val['order']['title'];
            $order['sample'] = $val['order']['sample'];

            $order['claims'] = $val['order']['claims'];

            $order['images'] = explode(',', $val['order']['images']);
            $order['parameter'] = $val['order']['parameter'];
            $order['order_no'] = $val['order']['order_no'];
//            $order['order_user_name'] = User::where('id', $val['order']['user_id'])->value('userName');
            $order['order_user_name'] = $val['order']['user_name'];

//            $order['user_type'] = $val['order']['user_type'];
            $order['status'] = $val['order']['status'];
            $order['creation_time'] = $val['order']['creation_time'];
            $order['company_id'] = $val['order']['company_id'];
            $order['current_company_id'] = $company_id;

            $order['pay_status'] = $val['order']['pay_status'];
            $order['need_type'] = $val['order']['need_type'];

            $order['total_price'] = $val['order']['total_price'];
            $order['company_name'] = $val['order']['company_name'];
//            $order['order_user_type'] = $val['order']['user_type'];
            $order['invite_status'] = $val['invite_status'];
            $order['is_accept'] = $val['order']['is_send'];
            $order['sure_finish'] = $val['order']['sure_finish'];
            $order['isAdministrator'] = $relate_info->isAdministrator;
            $order['type'] = $val['type'];
            $order['apply_cancel'] = $val['order']['apply_cancel'];
            $order['is_submit_version'] = $val['order']['is_submit_version'];

            $order['apply_finish'] = $val['order']['apply_finish'];

            $order['is_finish'] = $val['order']['sure_finish'];
            $order['is_pay'] = $val['order']['is_pay'];
            $order['is_send'] = $val['order']['is_send'];
//            $order['down_url'] = \config('domain_url').'storage/file/order_zip/'.$val['order']['id'].'_order.zip';
            $order['down_url'] = \config('domain_url').'api/down/downZip?order_id='.$val['order']['id']."&time=".uniqid();

            $order['level'] = $redis->getKeys('level_userid_' . $user_id);

            $group_list = UserOrderGroup::where('order_id', $val['order']['id'])->where('status',0)->get()->toArray();
            $file_list = UserOrderFile::where(['order_id' => $val['order']['id'], 'status' => 0])->get();

            if (empty($file_list)) {
                $order['user_uploade'] = [];
            } else {
                $file_list = $file_list->toArray();
                if (empty($file_list)) {
                    $order['user_uploade'] = [];
                } else {
                    foreach ($file_list as $fk => $fv) {
                        $order['user_uploade'][] = $fv['user_name'] . '---上传了文件';
                    }

                }


            }

            $need = [];
            $service = [];
            foreach ($group_list as $k => $v) {
                $jiayi = [];
//                switch ($v['user_type']) {
//                    case 1;
//                        $job = '设计师';
//                        break;
//                    case 2;
//                        $job = '制版师';
//                        break;
//                    case 3;
//                        $job = '制样师';
//                        break;
//                }
                if ($v['type'] == 1) {
                    $need[] = $v['company_name'] . '--' . $v['position_name'] . '--' . $v['user_name'];
                } else {
                    $service[] = $v['company_name'] . '--' . $v['position_name'] . '--' . $v['user_name'];
                }

            }
            $order['need'] = $need;
            $order['service'] = $service;
            $version_data = OrderVersion::where('order_id', $val['order']['id'])->get()->toArray();
            $version_list = [];
            $version_info = [];
            foreach ($version_data as $k => $v) {
                $version_list[] = '版本：' . $v['version_num'] . '---' . $v['add_time'];
                $version_info[$k]['version_num'] = $v['version_num'];
                $version_info[$k]['version_name'] = '版本：' . $v['version_num'] . '---' . $v['add_time'];
            }
            $order['version_list'] = $version_list;
            $order['version_info'] = $version_info;
            $icon = $this->getIcon($order);
            $order['icon'] = $icon;
            $list['data'][$key] = $order;


        }

        return $this->success('请求成功', $list);


    }

    function orderSource(RequestInterface $request)
    {
        $param = $request->all();
        $redis = RedisUtil::getInstance();
        $size = empty($param['size']) ? 10 : $param['size'];
        $user_info = $request->user_info;
        $user_id = $request->user_info['id'];
        $company_id = $this->request->switch_company;
        $company_id = empty($company_id) ? 0 : $company_id;

        $where = ['user_id' => $user_id, 'company_id' => $company_id, 'status' => 0];
        $relate_info = CompanyUserRelate::where(['user_id' => $user_id, 'company_id' => $company_id])->first();
        if (!empty($param['sn'])) {
            $where['order_no'] = $param['sn'];
        }

        if (!empty($param['invite_status']) && in_array($param['invite_status'], [1, 2, 3])) {

            $where['invite_status'] = $param['invite_status'];
        }
        if (!empty($param['is_finish'])) {

            $where['is_finish'] = $param['is_finish'];

        }
        if (empty($relate_info)) {
            return $this->error('用户信息缺失');
        }
        if (!empty($user_info['is_admin'])) {
            if (isset($where['is_finish'])) {
                $where['sure_finish'] = $where['is_finish'];
                unset($where['is_finish']);
            }

            $list = $this->adminOrderList($where, $param);
            return $this->success('返回成功', $list);
        }

        if (!empty($param['startTime']) && !empty($param['endTime'])) {
            $list = UserOrderGroup::with('order')->whereBetween('creation_time', [$param['startTime'], $param['endTime']])->where($where)->orderByDesc('creation_time')->paginate($size, ['*'], 'current');

        } else {
            $list = UserOrderGroup::with('order')->where($where)->orderByDesc('creation_time')->paginate($size, ['*'], 'current');

        }
        $list = json_decode(json_encode($list), true);
//        var_dump($list['data'][0]['order']);
        foreach ($list['data'] as $key => $val) {
            $order = [];
            $order['order_id'] = $val['order']['id'];
            $order['order_user_id'] = $val['order']['user_id'];
            $order['title'] = $val['order']['title'];
            $order['sample'] = $val['order']['sample'];

            $order['claims'] = $val['order']['claims'];

            $order['images'] = explode(',', $val['order']['images']);
            $order['parameter'] = $val['order']['parameter'];
            $order['order_no'] = $val['order']['order_no'];
            $order['order_user_name'] = User::where('id', $val['order']['user_id'])->value('userName');
//            $order['user_type'] = $val['order']['user_type'];
            $order['status'] = $val['order']['status'];
            $order['creation_time'] = $val['order']['creation_time'];
            $order['company_id'] = $val['order']['company_id'];
            $order['current_company_id'] = $company_id;

            $order['pay_status'] = $val['order']['pay_status'];

            $order['total_price'] = $val['order']['total_price'];
            $order['company_name'] = $val['order']['company_name'];
//            $order['order_user_type'] = $val['order']['user_type'];
            $order['invite_status'] = $val['invite_status'];
            $order['is_accept'] = $val['order']['is_send'];
            $order['sure_finish'] = $val['order']['sure_finish'];
            $order['isAdministrator'] = $relate_info->isAdministrator;
            $order['type'] = $val['type'];
            $order['apply_cancel'] = $val['order']['apply_cancel'];
            $order['is_submit_version'] = $val['order']['is_submit_version'];

            $order['apply_finish'] = $val['order']['apply_finish'];

            $order['is_finish'] = $val['order']['sure_finish'];
            $order['is_pay'] = $val['order']['is_pay'];
            $order['is_send'] = $val['order']['is_send'];
            $order['level'] = $redis->getKeys('level_userid_' . $user_id);

            $group_list = UserOrderGroup::where('order_id', $val['order']['id'])->get()->toArray();
            $file_list = UserOrderFile::where(['order_id' => $val['order']['id'], 'status' => 0])->get();

            if (empty($file_list)) {
                $order['user_uploade'] = [];
            } else {
                $file_list = $file_list->toArray();
                if (empty($file_list)) {
                    $order['user_uploade'] = [];
                } else {
                    foreach ($file_list as $fk => $fv) {
                        $order['user_uploade'][] = $fv['user_name'] . '---上传了文件';
                    }

                }


            }

            $need = [];
            $service = [];
            foreach ($group_list as $k => $v) {
                $jiayi = [];
//                switch ($v['user_type']) {
//                    case 1;
//                        $job = '设计师';
//                        break;
//                    case 2;
//                        $job = '制版师';
//                        break;
//                    case 3;
//                        $job = '制样师';
//                        break;
//                }
                if ($v['type'] == 1) {
                    $need[] = $v['company_name'] . '--' . $v['position_name'] . '--' . $v['user_name'];
                } else {
                    $service[] = $v['company_name'] . '--' . $v['position_name'] . '--' . $v['user_name'];
                }

            }
            $order['need'] = $need;
            $order['service'] = $service;
            $version_data = OrderVersion::where('order_id', $val['order']['id'])->get()->toArray();
            $version_list = [];
            $version_info = [];
            foreach ($version_data as $k => $v) {
                $version_list[] = '版本：' . $v['version_num'] . '---' . $v['add_time'];
                $version_info[$k]['version_num'] = $v['version_num'];
                $version_info[$k]['version_name'] = '版本：' . $v['version_num'] . '---' . $v['add_time'];
            }
            $order['version_list'] = $version_list;
            $order['version_info'] = $version_info;
            $icon = $this->getIcon($order);
            $order['icon'] = $icon;
            $list['data'][$key] = $order;


        }

        return $this->success('请求成功', $list);


    }


    function orderDetail(RequestInterface $request)
    {
        $param = $request->all();
        if (empty($param['order_id'])) {
            $this->error('参数缺失');
        }
        $user_info = $this->request->user_info;
        $groupOrder = UserOrderGroup::where(['order_id' => $param['order_id'], 'user_id' => $user_info['id']])->first();
        if (empty($groupOrder)) {
            return $this->error('仅限平台用户使用');
        }
        $order_detail = UserOrder::where('id', $param['order_id'])->first()->toArray();

//        （level 1,2,3 &&   type=1  && is_send=0）｜｜（level 2,3 &&   type=2  && is_send=1 && apply_finish==0）
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        $order_detail['control_service'] = 1;
//        if((in_array($level,[1,2,3]) && $groupOrder->type==1 && $order_detail['is_send']==0) || (in_array($level,[2,3]) && $groupOrder->type==2 && $order_detail['is_send']==1 && $order_detail['apply_finish']==0) ){
        if ((in_array($level, [1, 2, 3]) && $groupOrder->type == 1 && $order_detail['is_send'] == 0 && $order_detail['is_submit_version'] == 0) || (in_array($level, [2, 3]) && $groupOrder->type == 2 && $order_detail['is_send'] == 1 && $order_detail['is_submit_version'] == 0)) {

            $order_detail['control_service'] = 0;

        }


        $order_detail['type'] = $groupOrder->type;

        $order_detail['images'] = empty($order_detail['images']) ? [] : explode(',', $order_detail['images']);
//        $ban = UserOrderFile::where(['type' => 1, 'order_id' => $order_detail['id']])->get();
        $order_detail['ban'] = empty($ban) ? [] : $ban->toArray();
//        $yang = UserOrderFile::where(['type' => 2, 'order_id' => $order_detail['id']])->get();
        $order_detail['yang'] = empty($yang) ? [] : $yang->toArray();
//        $user_order_info = UserOrderInfo::where(['order_id' => $param['order_id']])->get()->toArray();
//        var_dump($user_order_info);
//        $user_order_info = array_column($user_order_info, null, 'user_type');

//var_dump($user_order_info);
        $detail['order'] = $order_detail;
//        $detail['sheji'] = empty($user_order_info[1]) ? [] : $user_order_info[1];
//        $detail['zhiban'] = empty($user_order_info[2]) ? [] : $user_order_info[2];
//        $detail['zhiyang'] = empty($user_order_info[3]) ? [] : $user_order_info[3];
        $detail['sheji'] = [];
        $detail['zhiban'] = [];
        $detail['zhiyang'] = [];


        return $this->success('返回成功', $detail);


    }


    /**
     * @param RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * 我的邀请列表
     */

    function invitedList(RequestInterface $request)
    {
        $user_info = $request->user_info;
        $param = $request->all();
        $order_id = $param['order_id'];
        if (empty($order_id)) {
            return $this->error('参数缺失');
        }
        $list = UserOrderGroup::where(['order_id' => $order_id, 'from_user_id' => $user_info['id']])->get();
        return $this->success('返回成功', $list);
    }

    //需求方邀请员工列表

    function requireStaffList(RequestInterface $request)
    {
        $param = $request->all();
        $where = [];
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }
        $order_id = $param['order_id'];
        $user_info = $request->user_info;
        $size = empty($param['size']) ? 100 : $param['size'];
        $company_id = $this->request->switch_company;
        if (isset($param['username'])) {
            $where['user_name'] = ['like', $param['username'] . '%'];
        }
        $user_info = $request->user_info;
//        $sys = CompanyUserRelate::where(['user_id' => $user_info['id']])->first();
//        if (empty($param['company_id']) && !empty($sys)) {
//            return $this->success('返回成功', []);
//        }
        if ($user_info['is_admin'] == 1) {
            return $this->success('返回成功', []);
        }
        $invite_list = UserOrderGroup::where(['order_id' => $order_id, 'status' => 0])->pluck('user_id')->toArray();
        $list = CompanyUserRelate::with(['user' => function ($query) use ($param) {
            $query->select('userName', 'id');
        }])->where(['company_id' => $company_id, 'status' => 1])->where($where)->whereNotIn('user_id', $invite_list)->paginate($size, ['*'], 'current');
        return $this->success('请求成功', $list);
    }

    //服务方邀请员工列表

    function serviceStaffList(RequestInterface $request)
    {
        $param = $request->all();
        $where = [];
        $order_id = $param['order_id'];
        if (empty($order_id)) {
            return $this->error('参数缺失');
        }
        if (isset($param['username'])) {
            $where['user_name'] = ['like', $param['username'] . '%'];
        }
        $user_info = $request->user_info;
        $redis = RedisUtil::getInstance();
        $company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        if (empty($company_id)) {
            return $this->success('返回成功', []);
        }

        $invite_list = UserOrderGroup::where(['order_id' => $order_id, 'from_user_id' => $user_info['id']])->pluck('user_id')->toArray();

        if (!empty($invite_list)) {
            $where['user_id'] = ['not in', $invite_list];
        }
//        var_dump($where);
//        echo '****&&&*******';
        $list = CompanyUserRelate::with(['user' => function ($query) use ($param) {
            $query->select('userName', 'id');
//            if(!empty($param['username'])){
//                $query->where('userName','like',$param['username'].'%');
//            }

        }])->where(['company_id' => $company_id, 'status' => 1])->where($where)->paginate(10);
//        var_dump($list);
        return $this->success('请求成功', $list);
    }


    //平台方邀请员工列表及（邀请公司  公司只有一个管理员及创建公司的）

    function sysCompanyList()
    {
        $company_list = UserCompany::where([])->get();
        return $this->success('返回成功', $company_list);
    }

    function inviteStaffCompany(RequestInterface $request)
    {
        $param = $request->all();
        if (empty($param['order_id'])) {
            return $this->error('order_id缺失');
        }
        $user_id = $request->user_info['id'];
//        $redis = RedisUtil::getInstance();
        $company_id = $request->switch_company;
//        $company_id = empty($company_id) ? 0 : $company_id;
        $company_user_relate = CompanyUserRelate::where(['user_id' => $user_id, 'company_id' => $company_id])->first();
        $group_order = UserOrderGroup::where(['user_id' => $user_id, 'order_id' => $param['order_id']])->first();
        if (!empty($request->user_info['is_admin']) || $group_order->type == 1) {
            $company_list = UserCompany::query()->get()->toArray();
        } else {
            if (empty($company_id)) {
                $company_list[] = ['id' => 0, 'company_name' => '云衣公社', 'address' => '杭州', 'remark' => '默认'];
            } else {
                $company_list[] = UserCompany::query()->where('id', $company_id)->first();

            }

        }
        return $this->success('返回成功', $company_list);


    }


    function downLoad(RequestInterface $request)
    {
        $param = $request->all();
        $order_id = $param['order_id'];
        $user_order_file = UserOrderFile::where(['order_id' => $order_id])->get()->toArray();

//
//        $code = array_column($room_info,'room_remark_code','id');
//        $hotel_names = array_column($room_info,'hotel_name','id');
//        $room_nums = array_column($room_info,'room_num','id');
        @$dfile = tempnam('/tmp', 'tmp');//产生一个临时文件，用于缓存下载文件
        $zip = new ImgeDown();
//            $image = array(
//                'https://i.loli.net/2018/05/31/5b0f51c4715a7.jpg',
//                'https://i.loli.net/2018/05/31/5b0f51e0b5f01.jpg',
//                'http://dev.lvtudiandian.com:8124/images/qrcode/7a47d3c6fab9fb17fa7ccb69127221fb.png',
//            );
//----------------------
        $filename = 'image.zip'; //下载的默认文件名
        foreach ($user_order_file as $key => $v) {
            if (!empty($v)) {
                $image_s_url = explode('.', $v['file_url']);
                $prex = $image_s_url[count($image_s_url) - 1];
                $image_name = $v['user_name'] . $v['id'];
                $save_name = $image_name . '.' . $prex;
                $save_name = iconv('utf-8', 'gbk//ignore', $save_name);
                echo $v['file_url'];
                echo '******';
                $zip->add_file(file_get_contents($v['file_url']), $save_name);
            }
//                $zip->add_file(file_get_contents($v),time().".jpg");
            // 添加打包的图片，第一个参数是图片内容，第二个参数是压缩包里面的显示的名称, 可包含路径
            // 或是想打包整个目录 用 $zip->add_path($image_path);
        }
//----------------------
        $zip->output($dfile);
// 下载文件
        ob_clean();
        header('Pragma: public');
        header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control:no-store, no-cache, must-revalidate');
        header('Cache-Control:pre-check=0, post-check=0, max-age=0');
        header('Content-Transfer-Encoding:binary');
        header('Content-Encoding:none');
        header('Content-type:multipart/form-data');
        header('Content-Disposition:attachment; filename="' . $filename . '"'); //设置下载的默认文件名
        header('Content-length:' . filesize($dfile));
        $fp = fopen($dfile, 'r');
        while (connection_status() == 0 && $buf = @fread($fp, 8192)) {
            echo $buf;
        }
        fclose($fp);
        @unlink($dfile);
        @flush();
        @ob_flush();
        exit();
    }


    /**
     * @param RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * 我的上传列表
     */
    function uploadList(RequestInterface $request)
    {
        $param = $request->all();
        $order_id = $param['order_id'];
        if (empty($order_id)) {
            return $this->error('参数缺失');
        }
        $user_info = $request->user_info;
        $user_id = $user_info['id'];
        $redis = RedisUtil::getInstance();
        $switch_company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $switch_company_id = empty($switch_company_id) ? 0 : $switch_company_id;

        $file = UserOrderFile::where(['user_id' => $user_id, 'order_id' => $order_id])->pluck('file_url');
        return $this->success('返回成功', $file);


    }

    function uploadListFile(RequestInterface $request)
    {
        $param = $request->all();
        $order_id = $param['order_id'];
        if (empty($order_id)) {
            return $this->error('参数缺失');
        }
        $user_info = $request->user_info;
        $user_id = $user_info['id'];
        $redis = RedisUtil::getInstance();
        $switch_company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $switch_company_id = empty($switch_company_id) ? 0 : $switch_company_id;

        $file = UserOrderFile::where(['order_id' => $order_id, 'status' => 0])->get()->toArray();
        $v_num = 0;
        $list = [];
        foreach ($file as $key => $value) {
            if ($value['file_type'] == 1) {
                $v_num++;
                $arr['version_num'] = '版本 ' . $v_num;
                $arr['file_url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['file_type'] = $value['file_type'];
                $arr['upload_date'] = $value['upload_date'];
                $arr['user_id'] = $value['user_id'];
                $arr['user_type'] = $value['type'];
                $arr['company_id'] = $value['company_id'];
//                $list[$value['file_type']][$value['user_name']][] = $arr;

            } else {
//                if(empty($value['file_type'])){
//                    echo '%$%$^$^%$%%#@#$%^&*';
//                }
                $arr['upload_date'] = $value['upload_date'];

                $arr['file_type'] = $value['file_type'];

                $arr['file_url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['user_id'] = $value['user_id'];
                $arr['user_type'] = $value['type'];
                $arr['company_id'] = $value['company_id'];

//                $list[$value['file_type']][$value['user_name']][] = $arr;
//                $list[$value['file_type']][$value['user_name']][] = $value['file_url'];
//                $list[$value['file_type']][] = $arr;

//                var_dump($arr);
            }
            $list[$value['file_type']][] = $arr;
            $arr = [];

//            $list[$value['user_name']][] = $file[$key];
        }
        return $this->success('返回成功', $list);


    }

    /**
     * 查看所有上传列表
     */

    function fileList(RequestInterface $request)
    {
        $param = $request->all();
        $order_id = $param['order_id'];

        if (empty($order_id)) {
            return $this->error('参数缺失');
        }
        $file = UserOrderFile::where(['order_id' => $order_id])->select('user_name', 'file_url')->get()->toArray();
        $list = [];
        foreach ($file as $val) {
            $list[$val['user_name']][] = $val['file_url'];
        }
//        $file = array_column($file,null,'user_name');
        return $this->success('返回成功', $list);


    }

//邀请加入 订单
    function applyEmploy(RequestInterface $request)
    {
        $from_user_info = $request->user_info;
        $company_id = $request->switch_company;
        $param = $request->all();
//        if (empty($param['user_id']) || empty($param['order_id']) || !isset($param['company_id'])) {
        if (empty($param['user_id']) || empty($param['order_id'])) {

            return $this->error('参数缺失');
        }
        $from_user_id = $from_user_info['id'];
        $user_info = User::where('id', $param['user_id'])->first()->toArray();

        $order_info = UserOrder::where(['id' => $param['order_id']])->first()->toArray();
        $is_invite = UserOrderGroup::where(['order_id' => $param['order_id'], 'user_id' => $param['user_id'], 'status' => 0])->first();
        if (!empty($is_invite)) {
            return $this->error('不可重复邀请');
        }
        $from_order_info = UserOrderGroup::where(['order_id' => $param['order_id'], 'user_id' => $from_user_id])->first();
        if (empty($from_order_info)) {
            $group_data['type'] = 2;
        } else {
//            if ($param['company_id'] != $company_id) {
//                $group_data['type'] = 2;
//            } else {
            $group_data['type'] = $from_order_info['type'];

//            }
        }
        $user_info_relate = CompanyUserRelate::where(['user_id' => $param['user_id'], 'company_id' => $company_id])->first()->toArray();
        if (empty($user_info_relate)) {
            return $this->error('信息缺失');
        }
        $group_data['company_id'] = $company_id;
//        $group_data['user_type'] = $user_info_relate['type'];
        $group_data['order_id'] = $param['order_id'];
        $group_data['user_id'] = $param['user_id'];
        $group_data['creation_time'] = date("Y-m-d H:i:s");
        $group_data['user_name'] = $user_info['userName'];
        $group_data['company_name'] = $user_info_relate['company_name'];
        $group_data['from_user_id'] = $from_user_id;
        $group_data['order_no'] = $order_info['order_no'];
//        $group_data['step'] = $order_info['step'];
        $group_data['is_from'] = 1;
        $group_data['position_id'] = $user_info_relate['position_id'];
        $group_data['position_name'] = $user_info_relate['position_name'];
        $group_data['department_id'] = $user_info_relate['department_id'];
        $group_data['department_name'] = $user_info_relate['department_name'];
        $group_data['invite_status'] = 2;
        $result = UserOrderGroup::insertGetId($group_data);
        $pushObject = new OrderPushTemplateController();
        $openid_arr = [];
        if(!empty($user_info['wx_openid'])){
            $openid_arr[] = $user_info['wx_openid'];
        }
        $pushObject->pushTemp($order_info,1,$openid_arr);
        return $this->success('邀请成功', []);

    }


    /**
     * 确认收款
     */
    function receiveMoney(RequestInterface $request)
    {
        $user_info = $request->user_info;
        $param = $request->all();
        if (empty($param['order_id']) || empty($param['money'])) {
            return $this->error('参数缺失');
        }
        $is_admin = CompanyUserRelate::where(['user_id' => $user_info['id']])->first();
        if (!empty($is_admin)) {
            return $this->error('没有该收款权限');
        }
        $update_data['step'] = 4;
        $update_data['pay_status'] = 1;
        $update_data['total_price'] = $param['money'];
        UserOrder::where(['id' => $param['order_id']])->update($update_data);
        UserOrderGroup::where('order_id', $param['order_id'])->update(['step' => 4]);
        return $this->success('确认收款成功');


    }

    /**
     * 接受邀请2接受3拒绝
     */
    function receiveApply(RequestInterface $request)
    {
        $user_info = $request->user_info;
        $param = $request->all();
        $company_id = $request->switch_company;
        try {
            Db::beginTransaction();

            if (empty($param['status']) || empty($param['order_id'])) {
                throw new Exception('参数缺失');
            }
            $group_data['invite_status'] = $param['status'];
            if (!in_array($param['status'], [2, 3])) {
                throw new Exception('参数错误');
            }
//        $update_order_date['step'] = 2;
            if ($param['status'] == 2) {
//            $group_data['is_accept'] = 1;
                $update_order_date['is_send'] = 1;
            }
            $group = UserOrderGroup::where(['order_id' => $param['order_id'], 'company_id' => $company_id])->update($group_data);
//            $is_accept = UserOrder::where('id', $param['order_id'])->value('is_accept');
//            if (empty($is_accept) && $param['status'] == 2) {
            if ($param['status'] == 2) {

                UserOrder::where('id', $param['order_id'])->update(['is_send' => 1]);

            }
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        if ($group) {
            Db::commit();

        } else {
            Db::rollBack();
            return $this->error();

        }
        return $this->success();
    }

    /**
     * 聊天记录
     */
    function talkRecord(RequestInterface $request)
    {
        $param = $request->all();

        if (empty($param['order_id'])) {
            return $this->error();
        }

        $result = UserOrderTalk::where(['order_id' => $param['order_id']])->get();
//        if(empty($result)){
//            return $this->success();
//        }
        return $this->success('返回成功', $result);


    }


    /**
     * 添加我的上传 0其他文件（包含excel）1打版文件（每个是一个版本）2设计图3成衣款式图3工艺图4版型图
     */

    function addMyUpload(RequestInterface $request)
    {

        $param = $request->all();
        $user_info = $request->user_info;
        if (empty($param['order_id']) || empty($param['file'])) {
            return $this->error('参数缺失');
        }
        $file_type = empty($param['file_type']) ? 0 : $param['file_type'];
        $data['order_id'] = $param['order_id'];
        $data['user_name'] = $user_info['userName'];
        $redis = RedisUtil::getInstance();
        $company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $company_id = empty($company_id) ? 0 : $company_id;
        $data['company_id'] = $company_id;
        $data['company_name'] = empty($company_id) ? '云衣公社' : UserCompany::where('id', $company_id)->value('company_name');
        $file_arr = $param['file'];
        $data['upload_date'] = date("Y-m-d H:i:s");
        $data['type'] = UserOrderGroup::where(['id' => $param['order_id'], 'user_id' => $user_info['id']])->value('user_type');
        $data['user_id'] = $user_info['id'];
        $data['file_type'] = $file_type;
        UserOrderFile::where(['order_id' => $param['order_id'], 'user_id' => $user_info['id'], 'file_type' => $file_type])->delete();
        foreach ($file_arr as $k => $v) {
            $data['file_url'] = $v;
            UserOrderFile::insertGetId($data);
        }
        return $this->success();
//        $redis->
    }


    function finishOrder(RequestInterface $request)
    {
//        $param = $request->all();
//        $user_info = $request->user_info;
//
//        $order_info = UserOrder::where('id', $param['order_id'])->first()->toArray();
//
//        if ($order_info['step'] != 2) {
//            return $this->error('不可确认完成');
//        }
//
//        UserOrder::where('id', $param['order_id'])->update(['step' => 3]);
//        UserOrderGroup::where('order_id', $param['order_id'])->update(['step' => 3]);
//
//        return $this->success();
        $param = $request->all();
        $user_info = $request->user_info;

        $order_info = UserOrder::where('id', $param['order_id'])->first()->toArray();

        if ($order_info['apply_finish'] != 1) {
            return $this->error('服务方未申请结单');
        }

//        UserOrder::where('id', $param['order_id'])->update(['step' => 3, 'is_finish' => 1, 'sure_finish' => 1]);
//        UserOrderGroup::where('order_id', $param['order_id'])->update(['step' => 3, 'is_finish' => 1]);
        UserOrder::where('id', $param['order_id'])->update(['sure_finish' => 1]);
        UserOrderGroup::where('order_id', $param['order_id'])->update(['is_finish' => 1]);
        $to_user_ids = UserOrderGroup::where(['order_id'=> $param['order_id'],'is_from'=>1])->pluck('user_id')->toArray();
        $openid_arr = [];
        if(!empty($to_user_ids)){
            $openid_arr = User::whereIn('id',$to_user_ids)->pluck('wx_openid')->toArray();
        }

        $pushObject = new OrderPushTemplateController();
        $pushObject->pushTemp($order_info,3,$openid_arr);
        return $this->success();


    }

    /*
     * 需求方确认完成订单
     */
    function needSureFinish(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;

        $order_info = UserOrder::where('id', $param['order_id'])->first()->toArray();

        if ($order_info['apply_finish'] != 1) {
            return $this->error('服务方未申请结单');
        }

        UserOrder::where('id', $param['order_id'])->update(['step' => 3, 'is_finish' => 1, 'sure_finish' => 1]);
        UserOrderGroup::where('order_id', $param['order_id'])->update(['step' => 3, 'is_finish' => 1]);

        return $this->success();


    }

    /*
     * 服务方申请结单
     */
    function serviceApplyFinish(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;

        $order_info = UserOrder::where('id', $param['order_id'])->first()->toArray();

        if ($order_info['apply_finish'] == 1) {
            return $this->error('不可重复申请结单');
        }

        UserOrder::where('id', $param['order_id'])->update(['apply_finish' => 1]);

        return $this->success();


    }

    function getDiscuss(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;
        $discuss = UserOrderDiscuss::where(['user_id' => $user_info['id'], 'order_id' => $param['order_id']])->first();
        return $this->success('请求成功', $discuss);


    }


    function addDiscuss(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;
        $order = UserOrder::where(['id' => $param['order_id']])->first()->toArray();
//        if ($order['step'] != 3) {
//            return $this->error('订单未完成不支持评论');
//        }
        $discuss = UserOrderDiscuss::where(['user_id' => $user_info['id'], 'order_id' => $param['order_id']])->first();
        if (!empty($discuss)) {
            return $this->error('不可重复评论');
        }
//        if($order['step']==4){
//        UserOrder::where('id', $param['order_id'])->update(['is_comment' => 1]);
//        }
        UserOrderDiscuss::insertGetId(['order_id' => $param['order_id'], 'content' => $param['content'], 'user_id' => $user_info['id'], 'user_name' => $user_info['userName']]);
        return $this->success();
    }


    function discussList(RequestInterface $request)
    {

        $param = $request->all();
        $user_info = $request->user_info;
        $discuss = UserOrderDiscuss::where(['order_id' => $param['order_id']])->get();
        return $this->success('返回成功', $discuss);


    }


    function orderGroupList()
    {

        $param = $this->request->all();
        $user_info = $this->request->user_info;
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }
        $company_id = $this->request->switch_company;

        $list = UserOrderGroup::where(['order_id' => $param['order_id'], 'invite_status' => 2, 'status' => 0, 'company_id' => $company_id])->where('user_id', '!=', $user_info['id'])->select('id as group_id', 'user_id', 'user_name', 'invite_status', 'company_id', 'company_name')->get()->toArray();
        return $this->success('返回成功', $list);


    }

    function outOrder()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        if (empty($param['group_id'])) {
            return $this->error('参数缺失');
        }
        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first();
        if ($user_info['is_admin'] != 1) {
            if ($relate_info->isAdministrator != 1) {
                return $this->error('非管理员不可踢出成员');

            }

            $info = UserOrderGroup::where('id', $param['group_id'])->first();
            if ($info->user_id == $user_info['id']) {
                return $this->error('不可操作自己');
            }
            if ($info->company_id != $company_id) {
                return $this->error('不可剔除团队之外的成员');

            }
        }

        UserOrderGroup::where('id', $param['group_id'])->update(['status' => 1]);

        return $this->success('踢出订单成功');


    }

    function shejiAdd()
    {
        $param = $this->request->all();
        $data['text'] = json_encode($param);
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }

        $info = UserOrderInfo::where(['user_type' => 1, 'order_id' => $param['order_id']])->first();
        if (empty($info)) {
            UserOrderInfo::insertGetId(['user_type' => 1, 'order_id' => $param['order_id'], 'text' => $data['text']]);
        } else {
            UserOrderInfo::where(['user_type' => 1, 'order_id' => $param['order_id']])->update(['text' => $data['text']]);

        }


        return $this->success('请求成功');


    }

    function zhibanAdd()
    {
        $param = $this->request->all();
        $data['text'] = json_encode($param);
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }

        $info = UserOrderInfo::where(['user_type' => 2, 'order_id' => $param['order_id']])->first();
        if (empty($info)) {
            UserOrderInfo::insertGetId(['user_type' => 2, 'order_id' => $param['order_id'], 'text' => $data['text']]);
        } else {
            UserOrderInfo::where(['user_type' => 2, 'order_id' => $param['order_id']])->update(['text' => $data['text']]);

        }


        return $this->success('请求成功');

    }

    function zhiyangAdd()
    {
        $param = $this->request->all();
        $data['text'] = json_encode($param);
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }

        $info = UserOrderInfo::where(['user_type' => 3, 'order_id' => $param['order_id']])->first();
        if (empty($info)) {
            UserOrderInfo::insertGetId(['user_type' => 3, 'order_id' => $param['order_id'], 'text' => $data['text']]);
        } else {
            UserOrderInfo::where(['user_type' => 3, 'order_id' => $param['order_id']])->update(['text' => $data['text']]);

        }


        return $this->success('请求成功');

    }

    function getDetail()
    {
        $param = $this->request->all();
        $where_version = [];
        if (!empty($param['version_num']) && $param['version_num'] != 'undefined') {
            $where_version['version_num'] = $param['version_num'];
        } else {
            $where_version['status'] = 0;

        }
        $user_info = $this->request->user_info;
        $order_id = empty($param['order_id']) ? 5 : $param['order_id'];
        $user_id = $user_info['id'];
        $redis = RedisUtil::getInstance();
        $switch_company_id = $this->request->switch_company;
        $file = UserOrderFile::where(['order_id' => $order_id])->where($where_version)->get()->toArray();
        $v_num = 0;
        $list = [];
        $order_group = UserOrderGroup::with('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->first();
//        $order_group = UserOrderGroup::with('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->first()->toArray();
        if (empty($order_group)) {
            return $this->error('仅限平台用户使用');
        }
        $order_group = $order_group->toArray();
        $result['is_send'] = $order_group['order']['is_send'];
        $result['type'] = $order_group['type'];
        $result['level'] = $redis->getKeys('level_userid_' . $user_id);
        foreach ($file as $key => $value) {
            if ($value['file_type'] == 1) {
                $v_num++;
                $arr['version_num'] = '';
                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['file_type'] = $value['file_type'];
                $arr['upload_date'] = $value['upload_date'];
                $arr['user_id'] = $value['user_id'];
//                $arr['user_type'] = $value['type'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];

//                $list[$value['file_type']][$value['user_name']][] = $arr;

            } else {
                $arr['upload_date'] = $value['upload_date'];

                $arr['file_type'] = $value['file_type'];

                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['user_id'] = $value['user_id'];
//                $arr['user_type'] = $value['type'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];

//                $list[$value['file_type']][$value['user_name']][] = $arr;
//                $list[$value['file_type']][$value['user_name']][] = $value['file_url'];
//                $list[$value['file_type']][] = $arr;

//                var_dump($arr);
            }
            $list[$value['file_type']]['file_type'] = $value['file_type'];
            $list[$value['file_type']]['list'][] = $arr;


//            $list[$value['user_name']][] = $file[$key];
        }
        $file = $list;
//        $design = DesignWriteDetail::where(['status' => 0, 'order_id' => $order_id])->where($where_version)->orderByDesc('id')->first();
//        $zb = PlatemakingWriteDetail::where(['status' => 0, 'order_id' => $order_id])->where($where_version)->first();
        $design = DesignWriteDetail::where(['order_id' => $order_id])->where($where_version)->orderByDesc('id')->first();
        $zb = PlatemakingWriteDetail::where(['order_id' => $order_id])->where($where_version)->first();
        if (!empty($zb->te_code)) {
            $zb->te_code = unserialize($zb->te_code);
        }
        if (!empty($design)) {
            $design = $design->toArray();
            unset($design['version_num']);
            unset($design['id']);
            unset($design['update_time']);
        }
        if (!empty($zb)) {
            $zb = $zb->toArray();
            unset($zb['id']);
            unset($zb['version_num']);
            unset($design['update_time']);
        }
        $form['design'] = $design;
        $form['zb'] = $zb;
        $f_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->pluck('id')->toArray();
        $file_ver = implode('', $f_list);
//        $order_detail = OrderDetail::where(['order_id' => $order_id, 'field_version' => $file_ver])->where($where_version)->first();
        $order_detail = OrderDetail::where(['order_id' => $order_id])->where($where_version)->first();

        if (!empty($order_detail->content)) {
            $order_detail->content = unserialize($order_detail->content);
        }
        $result['file'] = $file;
        $result['form'] = $form;
        $result['table'] = empty($order_detail->content) ? [] : $order_detail->content;
        $result['is_history'] = empty($param['version_num']) ? 0 : 1;
        $order_detail = UserOrder::where('id', $param['order_id'])->first()->toArray();
        $result['order_detail'] = $order_detail;
        return $this->success('详情返回成功', $result);


    }

//    function submitDetail()
//    {
//        $d_res = 0;
//        $p_res = 0;
//        $file_res = 0;
//        $detail_res = 0;
//        $param = $this->request->all();
//        var_dump($param);
//        $user_info = $this->request->user_info;
//        $company_id = $this->request->switch_company;
//        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toArray();
////        $user_type = $this->request->user_type;
//        $order_id = empty($param['order_id']) ? 5 : $param['order_id'];
//        $design = $param['form']['design'];
//        $design['order_id'] = $order_id;
//        $design['user_id'] = $user_info['id'];
//        $zb = $param['form']['zb'];
//        $zb['order_id'] = $order_id;
//        $zb['user_id'] = $user_info['id'];
//        $table = $param['table'];
//        $files = $param['file'];
//        $redis = RedisUtil::getInstance();
//        $is_subed = [$zb,$files,$table];
//        echo '11111111111';
//        $is_sub = $redis->setAdd('subed_order_id_'.$order_id,serialize($is_subed));
//        echo '111112222222111111';
//
////        var_dump($is_sub);echo '---------------------_____-----';
//        $order_group = UserOrderGroup::where(['order_id' => $order_id, 'user_id' => $user_info['id']])->first();
//        echo '11111133333333311111';
//
//        $order_info = UserOrder::where('id', $order_id)->first();
//        echo '1111144444444444111111';
//
////        try {
//            Db::beginTransaction();
////        var_dump($design);
////        unset($design['base_size']);
////        unset($design['platemaking_name']);
////        unset($design['write_date']);
////        unset($design['yesr_dress']);
////        unset($design['upload_time']);
//            unset($zb['upload_time']);
//
//            $zb['te_code'] = serialize($zb['te_code']);
//
//        echo '111155555555551111111';
//
//            DesignWriteDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
//            unset($design['id']);
//            if (empty($design['write_date'])) {
//                $design['write_date'] = date("Y-m-d H:i:s");
//            }
//            if (empty($design['yesr_dress'])) {
//                $design['yesr_dress'] = date("Y-m-d");
//            }
//            $d_res = DesignWriteDetail::insertGetId($design);
//        echo '111111666666666611111';
//
//        PlatemakingWriteDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
//        echo '1111117777777777711111';
//
//        unset($zb['id']);
//            $p_res = PlatemakingWriteDetail::insertGetId($zb);
//        echo '111111777777788888888777711111';
//
//        $detail['order_id'] = $order_id;
//            $detail['user_id'] = $user_info['id'];
//            $detail['content'] = serialize($table);
//            $detail['position_id'] = $relate_info['position_id'];
//            $detail['department_id'] = $relate_info['department_id'];
//            $detail['position_name'] = $relate_info['position_name'];
//            $detail['department_name'] = $relate_info['department_name'];
////            $detail['user_type'] = $this->request->user_type;
//        echo '1111117777779999999999999997777711111';
//
//        OrderDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
//        echo '11111231232312311';
//
//        $dv_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->pluck('id')->toArray();
//            $detail['field_version'] = implode('', $dv_list);
//            $detail_res = OrderDetail::insertGetId($detail);
//        echo '11111177779798789797979789777777711111';
//
//            $data['order_id'] = $order_id;
//            UserOrderFile::where(['order_id' => $order_id])->update(['status' => 1]);
//
//            foreach ($files as $k => $val) {
//
//                if (empty($val['list'])) {
//                    continue;
//                }
//
//                $file_type = empty($val['file_type']) ? 0 : $val['file_type'];
//                echo '111#######################1111';
//
//                foreach ($val['list'] as $fk => $fv) {
//                    $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first()->toArray();
//                    var_dump($relate_info);echo '111222333333****&&&&&&&';
//                    $data['name'] = empty($fv['name']) ? "无" : $fv['name'];
//
//                    $data['user_name'] = $fv['user_name'];
//                    $company_id = $fv['company_id'];
//                    $company_id = empty($company_id) ? 0 : $company_id;
//                    $data['company_id'] = $company_id;
//                    $data['company_name'] = empty($company_id) ? '云衣公社' : UserCompany::where('id', $company_id)->value('company_name');
//                    $data['file_url'] = $fv['url'];
//                    $data['upload_date'] = date("Y-m-d H:i:s");
////                    $data['type'] = $fv['user_type'];
//                    $data['user_id'] = $fv['user_id'];
//                    $data['file_type'] = $file_type;
//                    $data['position_id'] = $relate_info['position_id'];
//                    $data['department_id'] = $relate_info['department_id'];
//                    $data['position_name'] = $relate_info['position_name'];
//                    $data['department_name'] = $relate_info['department_name'];
//
//                    $file_res = UserOrderFile::insertGetId($data);
//
//
//                }
//
//
//            }
//            $version_num = OrderVersion::where('order_id', $order_id)->orderByDesc('id')->value('version_num');
//            $version_data['order_id'] = $order_id;
//            $version_data['add_time'] = date("Y-m-d H:i:s");
//            $version_data['version_num'] = empty($version_num) ? 1 : $version_num + 1;
//            if (($order_info->is_send == 0 || $order_group->type == 2) && $is_sub ) {
//                UserOrder::where('id', $order_id)->update(['apply_finish' => 1]);
//                OrderVersion::insert($version_data);
//            }
//
//
////        } catch (\Exception $e) {
////            Db::rollBack();
////            return $this->error($e->getMessage());
////        }
//        Db::commit();
//        return $this->success();
//    }
    function submitDetail()
    {
        $d_res = 0;
        $p_res = 0;
        $file_res = 0;
        $detail_res = 0;
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toArray();
//        $user_type = $this->request->user_type;
        $order_id = empty($param['order_id']) ? 5 : $param['order_id'];
        $design = $param['form']['design'];
        $design['order_id'] = $order_id;
        $design['user_id'] = $user_info['id'];
        $zb = $param['form']['zb'];
        $zb['order_id'] = $order_id;
        $zb['user_id'] = $user_info['id'];
        $table = $param['table'];
        $files = $param['file'];
        $redis = RedisUtil::getInstance();
        $is_subed = [$zb, $files, $table];
        $is_sub = $redis->setAdd('subed_order_id_' . $order_id, serialize($is_subed));
        var_dump($is_sub);
        echo '*************&&&&&&&&&&&**********';
        $version_num = OrderVersion::where('order_id', $order_id)->orderByDesc('id')->value('version_num');
        $version_data['order_id'] = $order_id;
        $version_data['add_time'] = date("Y-m-d H:i:s");
//        $version_data['version_num'] = empty($version_num) ? 1 : $version_num + 1;
        if (!$is_sub) {
            $version_data['version_num'] = empty($version_num) ? 0 : $version_num;
        } else {
            $version_data['version_num'] = $version_num + 1;

        }
        $order_group = UserOrderGroup::where(['order_id' => $order_id, 'user_id' => $user_info['id']])->first();

        $order_info = UserOrder::where('id', $order_id)->first();

        try {
            Db::beginTransaction();
            unset($zb['upload_time']);

            $zb['te_code'] = serialize($zb['te_code']);
            DesignWriteDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            unset($design['id']);
            if (empty($design['write_date'])) {
                $design['write_date'] = date("Y-m-d H:i:s");
            }
            if (empty($design['yesr_dress'])) {
                $design['yesr_dress'] = date("Y-m-d");
            }
            $design['version_num'] = $version_data['version_num'];

            $d_res = DesignWriteDetail::insertGetId($design);
            if ($is_sub) {
                PlatemakingWriteDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
                unset($zb['id']);
                $zb['version_num'] = $version_data['version_num'];

                $p_res = PlatemakingWriteDetail::insertGetId($zb);
                $detail['order_id'] = $order_id;
                $detail['user_id'] = $user_info['id'];
                $detail['content'] = serialize($table);
                $detail['position_id'] = $relate_info['position_id'];
                $detail['department_id'] = $relate_info['department_id'];
                $detail['position_name'] = $relate_info['position_name'];
                $detail['department_name'] = $relate_info['department_name'];
                OrderDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
                $dv_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->pluck('id')->toArray();
                echo '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~';
                var_dump($dv_list);
                echo '------------------------------------------';
                $detail['field_version'] = implode('', $dv_list);
                $detail['version_num'] = $version_data['version_num'];
                $detail_res = OrderDetail::insertGetId($detail);
//                echo OrderFieldVersion::where(['order_id' => $param['order_id'],'status'=>0])->toSql();
                $dv_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->get()->toArray();
//                var_dump($dv_list);
                foreach ($dv_list as $v) {
                    OrderFieldVersion::where('id', $v['id'])->update(['status' => 1, 'version_no' => $version_data['version_num']]);
//                    $v['version_no'] = $version_data['version_num'];
                    unset($v['id']);
                    unset($v['version_no']);
                    OrderFieldVersion::query()->insertGetId($v);

                }

                $data['order_id'] = $order_id;
                UserOrderFile::where(['order_id' => $order_id])->update(['status' => 1]);

                foreach ($files as $k => $val) {

                    if (empty($val['list'])) {
                        continue;
                    }

                    $file_type = empty($val['file_type']) ? 0 : $val['file_type'];
                    foreach ($val['list'] as $fk => $fv) {
                        $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first()->toArray();
                        $data['name'] = empty($fv['name']) ? "无" : $fv['name'];

                        $data['user_name'] = $fv['user_name'];
                        $company_id = $fv['company_id'];
                        $company_id = empty($company_id) ? 0 : $company_id;
                        $data['company_id'] = $company_id;
                        $data['company_name'] = empty($company_id) ? '云衣公社' : UserCompany::where('id', $company_id)->value('company_name');
                        $data['file_url'] = $fv['url'];
                        $data['upload_date'] = date("Y-m-d H:i:s");
//                    $data['type'] = $fv['user_type'];
                        $data['user_id'] = $fv['user_id'];
                        $data['file_type'] = $file_type;
                        $data['position_id'] = $relate_info['position_id'];
                        $data['department_id'] = $relate_info['department_id'];
                        $data['position_name'] = $relate_info['position_name'];
                        $data['department_name'] = $relate_info['department_name'];
                        $data['version_num'] = $version_data['version_num'];

                        $file_res = UserOrderFile::insertGetId($data);


                    }


                }

                if (($order_info->is_send == 0 || $order_group->type == 2) && $is_sub) {
//                    UserOrder::where('id', $order_id)->update(['apply_finish' => 1]);
                    UserOrder::where('id', $order_id)->update(['is_submit_version' => 1]);

//                UserOrder::where('id', $order_id)->update(['is_submit_version' => 1]);

                    OrderVersion::insert($version_data);
                }
            }
            $data_zip['order_id'] = $order_id;
            $this->producer->produce(new OrderExportProducer($data_zip));


        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $from_user_ids = UserOrderGroup::where(['order_id'=> $param['order_id'],'is_from'=>0])->pluck('user_id')->toArray();
        $openid_arr = [];
        if(!empty($from_user_ids)){
            $openid_arr = User::whereIn('id',$from_user_ids)->pluck('wx_openid')->toArray();
        }

        $pushObject = new OrderPushTemplateController();
        $pushObject->pushTemp($order_info,2,$openid_arr);
        return $this->success();
    }

    /**
     * 修改--打回
     */
    function repeat()
    {
        $param = $this->request->all();
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }
//        UserOrder::where('id', $param['order_id'])->update(['apply_finish' => 0]);
        UserOrder::where('id', $param['order_id'])->update(['is_submit_version' => 0, 'apply_finish' => 0]);

        return $this->success();
    }

    function readTxt()
    {
       echo $password = UserAuthService::generateFormattedPassword("135069");
       exit;

//        $filepath = '../oo.txt';
//        $file = fopen($filepath,"r");   //打开文件
//        //检测指针是否到达文件的未端
//        while(! feof($file)) {
////            echo fgets($file). "<br />";
//           $arr =  explode(',',fgets($file));
//           $data = ['sh_code'=>$arr[0],'trade_date'=>$arr[1],'trade_time'=>$arr[2],'order_no'=>$arr[3],'pay_method'=>$arr[4],'total_money'=>$arr[5],'qsuan_money'=>$arr[6],'shouxufei'=>$arr[7],'feilv'=>$arr[8]];
//           BandRecord::insert($data);
////           var_dump($arr);
//        }
//        fclose($file);//关闭被打开的文件
        $bom = new ExcelBom();
        $res = $bom->readFile('../底座管理.xlsx');
        foreach ($res as $k=>$v) {
            if($k!= 1 ){

                $data['sn'] = $v['A'];
                $data['sn_url'] = 'http://weixin.qq.com/r/cxzN1ZfE1sqprdO290lh?sn=/mp/'.$v['A'];
                Db::table("sn_url")->insert($data);
            }

//            if ($v['J'] == '反交易') {
//                $data = ['trade_date' => trim($v['A']), 'jigou_code' => $v['B'], 'shop_code' => $v['C'], 'order_code' => $v['D'], 'liushui_code' => $v['E'], 'pay_method' => $v['F'], 'pay_money' => $v['G'], 'fangxiang' => $v['J']];
//                SumBand::insert($data);
//            }

        }

//       var_dump($res);
//

//       echo count($a);
//           echo count($a).'*&&&&&**********';
//        $list = [];
//        foreach ($a as $v) {
////           $info =  BandDui::where('order_no',$v)->first();
////           if(empty($info)){
////               $list[] = $v;
////           }
//            BandDui::where('order_no', $v)->update(['is_exit' => 1]);
//        }
////        var_dump($list);

    }


    function readTxts()
    {

//
//        $order_no_arr = BandDui::where('status','部分退款')->pluck('order_no');
//        echo json_encode($order_no_arr);
//        $filepath = '../ca.txt';
//        $file = fopen($filepath, "r");   //打开文件
//        //检测指针是否到达文件的未端
//        while (!feof($file)) {
////            echo fgets($file). "<br />";
//            $arr = explode(',', fgets($file));
//            if ($arr[0] == 'TL1229') {
//                $data = ['sh_code' => $arr[0], 'trade_date' => $arr[1], 'trade_time' => $arr[2], 'order_no' => $arr[3], 'pay_method' => $arr[4], 'total_money' => $arr[5], 'qsuan_money' => $arr[6], 'shouxufei' => $arr[7], 'feilv' => $arr[8]];
//                BandRecord::insert($data);
//            }
//
////           var_dump($arr);
//        }
        $res = [
            '56667' => "1000",
            '56682' => "1500",
            '56681' => "5500",
            '56674' => "2300",
            '56685' => "1500",
            '56688' => "1500",
            '56692' => "5500",
            '56693' => "1500",
            '56695' => "6500",
            '56703' => "1500",
            '56704' => "4500",
            '56718' => "5500",
            '56722' => "5500",
            '56723' => "1500",
            '56740' => "4000",
            '56744' => "3500",
            '56752' => "4500",
            '56754' => "1500",
            '56756' => "3000",
            '56783' => "1500",
            '56772' => "1800",
            '56797' => "2000",
            '56790' => "2000",
            '56798' => "1500",
            '56799' => "1500",
            '56816' => "2500",
        ];
        foreach ($res as $key => $val) {
            BandRecord::where('order_no', trim($key))->update(['erp_refund_money' => number_format($val / 100, 2)]);
        }
    }

    /**
     * @Inject()
     * @var Producer
     */
    private $producer;

    function test()
    {
        $this->producer->produce(new DemoProducer('test' . date('Y-m-d H:i:s')));
        var_dump('done');
    }


    function orderSend()
    {

        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $param = $this->request->all();
        try {

            Db::beginTransaction();
            if (empty($param['order_id']) || !isset($param['company_id'])) {
                return $this->error('参数缺失');
            }
//            $from_user_id = $from_user_info['id'];
//            $user_info = User::where('id', $param['user_id'])->first()->toArray();

            $order_info = UserOrder::where(['id' => $param['order_id']])->first()->toArray();
            if ($order_info['is_send'] == 1) {
                throw new Exception('订单已被派送成功，不可再次派送');

            }
            $is_send = UserOrderGroup::where(['order_id' => $param['order_id'], 'status' => 0, 'type' => 2])->first();

//            $is_invite = UserOrderGroup::where(['order_id' => $param['order_id'], 'company_id' => $param['company_id'], 'status' => 0])->first();
            if (!empty($is_send)) {
                if ($is_send->company_id == $param['company_id']) {
                    throw new Exception('不可重复派单给当前公司');
                } else {
                    throw new Exception('该订单已被分派');

                }
//                return $this->error('不可重复邀请');
            }

            $from_order_info = UserOrderGroup::where(['order_id' => $param['order_id'], 'user_id' => $user_info['id']])->first();
            if (empty($from_order_info) || $from_order_info->type != 1) {
                throw new Exception('非需求方不可派单');
            }

            $group_data['type'] = 2;

            $user_info_relate = CompanyUserRelate::where(['company_id' => $param['company_id'], 'isAdministrator' => 1])->first()->toArray();
            $group_data['company_id'] = $param['company_id'];
//            $group_data['user_type'] = $user_info_relate['type'];
            $group_data['order_id'] = $param['order_id'];
            $group_data['user_id'] = $user_info_relate['user_id'];
            $group_data['creation_time'] = date("Y-m-d H:i:s");
            $group_data['user_name'] = $user_info_relate['user_name'];
            $group_data['company_name'] = $user_info_relate['company_name'];
//            $group_data['from_user_id'] = $user_info['id'];
            $group_data['order_no'] = $order_info['order_no'];
            $group_data['position_id'] = $user_info_relate['position_id'];
            $group_data['position_name'] = $user_info_relate['position_name'];
            $group_data['department_id'] = $user_info_relate['department_id'];
            $group_data['department_name'] = $user_info_relate['department_name'];
//            $group_data['step'] = $order_info['step'];
//            $group_data['is_from'] = 1;
            $result = UserOrderGroup::insertGetId($group_data);
            $send_data['user_id'] = $user_info['id'];
            $send_data['user_name'] = $user_info['userName'];

            $send_data['company_id'] = $param['company_id'];
            $send_data['company_name'] = $user_info_relate['company_name'];
            $send_data['from_company_id'] = $company_id;
            $send_data['from_company_name'] = $order_info['company_name'];
            $where = [];
            if(empty($company_id)){
                $where['user_id'] = $user_info['id'];
                $where['type'] = 0;
                $send_data['type'] = 0;
//                OrderSendNum::where('user_id',$user_info['id'])->first();
            }else{
                $where['from_company_id'] = $company_id;
                $where['type'] = 1;

                $send_data['type'] = 1;
//                OrderSendNum::where('from_company_id',$company_id)->first();


            }
            $is_send = OrderSendNum::where($where)->first();
            if(empty($is_send)){
                $send_data['add_time'] = date("Y-m-d H:i:s");
                OrderSendNum::insertGetId($send_data);
            }else{
                $send_data['send_num'] = $is_send['send_num']+1;
                var_dump($where);
                OrderSendNum::query()->where($where)->update($send_data);
            }

        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        $openid_arr = [];
        $admin_info = User::where('id',$user_info_relate['user_id'])->first()->toArray();

        if(!empty($admin_info['wx_openid'])){
            $openid_arr[] = $admin_info['wx_openid'];
        }
        $pushObject = new OrderPushTemplateController();
        $pushObject->pushTemp($order_info,1,$openid_arr);
        return $this->success('派单成功', []);


    }


    /*
     * 取消派送
     */
    function cancelSend()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }
        $order_info = UserOrder::where('id', $param['order_id'])->first();
        if ($order_info->is_send == 1) {
            return $this->error('派送成功不可取消');
        }
        UserOrder::where('id', $order_info->id)->update(['is_send' => 0]);
        UserOrderGroup::where(['order_id' => $order_info->id])->where('company_id','!=',$company_id)->update(['status' => 1]);
        return $this->success();
    }


    function sendCompanyList(RequestInterface $request)
    {
        $param = $request->all();
        if (empty($param['order_id'])) {
            return $this->error('order_id缺失');
        }
        $user_id = $request->user_info['id'];
//        $redis = RedisUtil::getInstance();
        $company_id = $request->switch_company;
//        $company_id = empty($company_id) ? 0 : $company_id;
        $company_user_relate = CompanyUserRelate::where(['user_id' => $user_id, 'company_id' => $company_id])->first();
        $group_order = UserOrderGroup::where(['user_id' => $user_id, 'order_id' => $param['order_id']])->first();

        if (!empty($group_order) && $group_order->type != 1) {
            return $this->error('仅限需求方请求');
        }
        if (empty($group_order)) {
            return $this->error('信息缺失');

        }
//        if (!empty($request->user_info['is_admin']) || $group_order->type == 1) {
//            $company_list = UserCompany::query()->get()->toArray();
//        } else {
//            if (empty($company_id)) {
//                $company_list[] = ['id' => 0, 'company_name' => '云衣公社', 'address' => '杭州', 'remark' => '默认'];
//            } else {
//        echo UserCompany::query()->where('id', '!=', $company_id)->where('level','=',2)->where('expire_time','>',date("Y-m-d H:i:s"))->orWhere('level','=',3)->toSql();
        $company_list = UserCompany::query()->where('id', '!=', $company_id)->where('level', '=', 2)->where('expire_time', '>', date("Y-m-d H:i:s"))->orWhere('level', '=', 3)->get()->toArray();
        $service = UserOrderGroup::where(['order_id' => $param['order_id'], 'type' => 2, 'status' => 0])->first();
        foreach ($company_list as $k => $v) {
            if ($v['id'] == $company_id) {
                unset($company_list[$k]);
                continue;
            }
            if (empty($service)) {
                $company_list[$k]['is_invite'] = 0;
            } else {
                if ($v['id'] == $service->company_id) {
                    $company_list[$k]['is_invite'] = 1;
                } else {
                    $company_list[$k]['is_invite'] = 0;

                }
            }

        }
        $company_list = array_values($company_list);
        if(empty($company_id)){
            $where = ['user_id'=>$user_id,'type'=>0,'from_company_id'=>0];
        }else{
            $where = ['type'=>1,'from_company_id'=>$company_id];
//            OrderSendNum::where()->first();

        }
        $order_send = OrderSendNum::where($where)->first();
        if(empty($order_send)){
            echo '*****&&&&&&&zi营z*****&&&&&&&zi营z*****&&&&&&&zi营z';
            $default_company = UserCompany::where(['level'=>3])->where('id','!=',$company_id)->get()->toArray();
        }else{
            echo '**&&&&&&&上次*******&&&&&&&上次*******&&&&&&&上次*******&&&&&&&上次*******&&&&&&&上次*******&&&&&&&上次*****';

            $default_company = UserCompany::where('id',$order_send->company_id)->get()->toArray();
        }
        foreach ($default_company as $k => $v) {

            if (empty($service)) {
                $default_company[$k]['is_invite'] = 0;
            } else {
                if ($v['id'] == $service->company_id) {
                    $default_company[$k]['is_invite'] = 1;
                } else {
                    $default_company[$k]['is_invite'] = 0;

                }
            }

        }
        echo '****(((((((((';
        var_dump($default_company);
        $list['select_list'] = $company_list;
        $list['default_list'] = $default_company;
       // return $this->success('返回成功', $company_list);

        return $this->success('返回成功', $list);


    }


    function applyCancel()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');

        }
        $order = UserOrder::where('id', $param['order_id'])->first();
        $group_order = UserOrderGroup::where(['user_id' => $user_info['id'], 'status' => 0, 'order_id' => $param['order_id']])->first();
        if (empty($order) || empty($group_order)) {
            return $this->error('信息获取错误');
        }
        if ($order->is_send == 1 && $order->sure_finish == 0 && $group_order->type == 1) {
            UserOrder::where('id', $param['order_id'])->update(['apply_cancel' => 1]);
        } else {
            return $this->error('当前状态不支持取消');
        }
        return $this->success('取消成功');


    }


    function agreeOrder()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        try {
            Db::beginTransaction();

            if (empty($param['order_id'])) {
                throw new \Exception('参数缺失');

            }

            $order = UserOrder::where('id', $param['order_id'])->first();
            $group_order = UserOrderGroup::where(['user_id' => $user_info['id'], 'status' => 0, 'order_id' => $param['order_id']])->first();
            if (empty($order) || empty($group_order)) {
                throw new \Exception('信息获取错误');
            }
            if ($order->is_send == 1 && $order->sure_finish == 0 && $group_order->type == 2 && $order->apply_cancel == 1) {
                $order_res = UserOrder::where('id', $param['order_id'])->update(['agree_cancel' => 1, 'is_send' => 0, 'apply_finish' => 0, 'apply_cancel' => 0]);
                $group_res = UserOrderGroup::where(['order_id' => $param['order_id'], 'type' => 2])->update(['status' => 1]);
            } else {
                throw new \Exception('当前状态不支持同意取消');
            }
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        if ($order_res && $group_res) {
            Db::commit();
        } else {
            Db::rollBack();
        }
        return $this->success();
    }


    function invalidOrder()
    {
        $user_info = $this->request->user_info;
        if ($user_info['is_admin'] != 1) {
            return $this->error('不具有该操作权限');
        }

        $param = $this->request->all();
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }

        UserOrder::where('id', $param['order_id'])->update(['is_invalid' => 1, 'status' => 1]);
        UserOrderGroup::where('order_id', $param['order_id'])->update(['status' => 1]);

        return $this->success();

    }

    function ttee()
    {

        $redis = RedisUtil::getInstance();
        $r = $redis->setNxClose('a', 'v', 10);
        $r1 = $redis->setNxClose('a', 'v1', 10);
        var_dump($r);
        var_dump($r1);
        echo BASE_PATH . '/public/file.csv';
        return $this->error(BASE_PATH . '/oo.txt');
//                return $this->response->download(BASE_PATH . '/public/file.csv', 'filename.csv');

//        return $this->error();
    }

    public function index(ResponseInterface $response): Psr7ResponseInterface
    {
        return $response->download(BASE_PATH . '/oo.txt', 'filename.csv');
    }


    function downZip(ResponseInterface $response)
    {
        $param = $this->request->all();
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }
        $zip_path = BASE_PATH . '/storage/file/order_zip';

//        if (!file_exists($dir)) {
        $zip_name = $zip_path . '/' . $param['order_id'] . '_order.zip';
        if (!is_file($zip_name)) {
            return $this->error('没有提交可下载的的版本，下载失败');
        }
        return $response->download($zip_name, $param['order_id'] .time(). '_order.zip');

    }

    function saveDetail()
    {
        $d_res = 0;
        $p_res = 0;
        $file_res = 0;
        $detail_res = 0;
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        echo CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->toSql();
        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toArray();
        var_dump($relate_info);
//        $user_type = $this->request->user_type;
        $order_id = empty($param['order_id']) ? 5 : $param['order_id'];
        $design = $param['form']['design'];
        $design['order_id'] = $order_id;
        $design['user_id'] = $user_info['id'];
        $zb = $param['form']['zb'];
        $zb['order_id'] = $order_id;
        $zb['user_id'] = $user_info['id'];
        $table = $param['table'];
        $files = $param['file'];
        $redis = RedisUtil::getInstance();
        $order_group = UserOrderGroup::where(['order_id' => $order_id, 'user_id' => $user_info['id']])->first();
        $order_info = UserOrder::where('id', $order_id)->first();
        try {
            Db::beginTransaction();
            unset($zb['upload_time']);
            $zb['te_code'] = serialize($zb['te_code']);
            DesignWriteDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            unset($design['id']);
            if (empty($design['write_date'])) {
                $design['write_date'] = date("Y-m-d H:i:s");
            }
            if (empty($design['yesr_dress'])) {
                $design['yesr_dress'] = date("Y-m-d");
            }

            $d_res = DesignWriteDetail::insertGetId($design);
//            if($is_sub) {
            PlatemakingWriteDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            unset($zb['id']);
            $p_res = PlatemakingWriteDetail::insertGetId($zb);
            $detail['order_id'] = $order_id;
            $detail['user_id'] = $user_info['id'];
            $detail['content'] = serialize($table);
            $detail['position_id'] = $relate_info['position_id'];
            $detail['department_id'] = $relate_info['department_id'];
            $detail['position_name'] = $relate_info['position_name'];
            $detail['department_name'] = $relate_info['department_name'];
            OrderDetail::where(['order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            echo '~~~~~~~~~~~~~~~~~~~~~~000000000~~~~~~~~~~~~~~~~~~~~';
            $dv_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->pluck('id')->toArray();
            echo '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~';
            var_dump($dv_list);
            echo '------------------------------------------';
            $detail['field_version'] = implode('', $dv_list);
            $detail_res = OrderDetail::insertGetId($detail);
            $dv_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->get()->toArray();
//            foreach ($dv_list as $v) {
//                OrderFieldVersion::where('id', $v['id'])->update(['status' => 1]);
//                unset($v['id']);
//                unset($v['version_no']);
//                OrderFieldVersion::query()->insertGetId($v);
//
//            }

            $data['order_id'] = $order_id;
            UserOrderFile::where(['order_id' => $order_id])->update(['status' => 1]);

            foreach ($files as $k => $val) {

                if (empty($val['list'])) {
                    continue;
                }

                $file_type = empty($val['file_type']) ? 0 : $val['file_type'];
                foreach ($val['list'] as $fk => $fv) {
                    echo '~~~~~~~~~~~~~~~~~~~~~~~~6666666~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~';
                    echo  $fv['user_id'].'@';
                    echo $fv['company_id'];
                    echo CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->toSql();
                    $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first()->toArray();
                    echo '~~~~~~~~~~~~~~~~~~~~~~~~777777777~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~';

                    $data['name'] = empty($fv['name']) ? "无" : $fv['name'];

                    $data['user_name'] = $fv['user_name'];
                    $company_id = $fv['company_id'];
                    $company_id = empty($company_id) ? 0 : $company_id;
                    $data['company_id'] = $company_id;
                    $data['company_name'] = empty($company_id) ? '云衣公社' : UserCompany::where('id', $company_id)->value('company_name');
                    $data['file_url'] = $fv['url'];
                    $data['upload_date'] = date("Y-m-d H:i:s");
//                    $data['type'] = $fv['user_type'];
                    $data['user_id'] = $fv['user_id'];
                    $data['file_type'] = $file_type;
                    $data['position_id'] = $relate_info['position_id'];
                    $data['department_id'] = $relate_info['department_id'];
                    $data['position_name'] = $relate_info['position_name'];
                    $data['department_name'] = $relate_info['department_name'];

                    $file_res = UserOrderFile::insertGetId($data);


                }


            }

            $data_zip['order_id'] = $order_id;
//            $this->producer->produce(new OrderExportProducer($data_zip));


        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        return $this->success();
    }
}


