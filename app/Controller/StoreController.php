<?php


namespace App\Controller;

use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Amqp\Producer\DemoProducer;
use App\Amqp\Producer\OrderExportProducer;
use App\Amqp\Producer\OrderProducer;
use App\Amqp\Producer\StoreExportProducer;
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
use App\Model\CompanyFieldSet;
use App\Model\CompanyUserRelate;
use App\Model\DesignWriteDetail;
use App\Model\OrderDetail;
use App\Model\OrderFieldVersion;
use App\Model\OrderSendNum;
use App\Model\OrderVersion;
use App\Model\PlatemakingWriteDetail;
use App\Model\StoreDetail;
use App\Model\StoreFieldVersion;
use App\Model\StoreFile;
use App\Model\StoreOrder;
use App\Model\StoreTe;
use App\Model\StyleParam;
use App\Model\SumBand;
use App\Model\SystemTypeField;
use App\Model\User;
use App\Model\UserCompany;
use App\Model\UserOrder;
use App\Model\UserOrderDiscuss;
use App\Model\UserOrderFile;
use App\Model\UserOrderGroup;
use App\Model\UserOrderInfo;
use App\Model\UserOrderTalk;
use App\Model\VersionField;
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
use Hyperf\Utils\Arr;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;
use Symfony\Component\HttpKernel\Tests\HttpCache\StoreTest;

//

/**
 * Class MenuController
 * @package App\Controller
 * @AutoController(prefix="/api/store")
 * @Middlewares({
 *  @Middleware(CheckToken::class)
 * })
 */
class StoreController extends BaseController
{

    /**
     * @Inject()
     * @var Producer
     */
    private $producer;

//    function __construct(ResponseInterface $response)
//    {
//        parent::__construct($response);
//        $user_info = $this->request->user_info;
//        $company_id = $this->request->switch_company;
//        $is_admin = $user_info['is_admin'];
//        $redis = RedisUtil::getInstance();
//        $level = $redis->getKeys('level_userid_' . $user_info['id']);
//        if (empty($company_id) && empty($is_admin)) {
//            return $this->error('个人用户不支持数据库使用');
//        }
////        if(empty($company_id)){
////            return $this->error('个人用户不支持数据库使用');
////
////        }
//        if (empty($level) && empty($is_admin)) {
//            return $this->error('公司该服务到期请续费，方可继续使用');
//
//        }
//
//    }

    function index()
    {
        return $this->error();
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

    function createStore()
    {
        $d_res = 0;
        $p_res = 0;
        $file_res = 0;
        $detail_res = 0;
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }

        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
//        echo $user_info['id'].'&&&&&&'.$company_id;
//        echo CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toSql();
        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toArray();
        $store_data['store_no'] = $this->get_ordersn_big();

//        $redis = RedisUtil::getInstance();
        $order_id = empty($param['store_order_id']) ? 0 : $param['store_order_id'];

        try {
            Db::beginTransaction();
            $store = $param['form']['store_order'];

            if (!isset($store['number']) || !isset($store['sex_name']) || !isset($store['decade']) || !isset($store['category']) || !isset($store['season']) || !isset($store['style']) || !isset($store['zb_name']) || !isset($store['write_date']) || !isset($store['te_code'])) {
                throw new \Exception('参数缺失');
            } else {
//                if (!is_array($store['te_code_arr'])) {
//                    throw new \Exception('te_code参数错误');
//
//                }
            }

            $store_data['number'] = $store['number'];
            $store_data['sex'] = empty($store['sex_name']) ? 1 : $store['sex_name'];

//            $store_data['decade'] = empty($store['decade']) ? date("Y") : date("Y", strtotime($store['decade']));
            $store_data['decade'] = empty($store['decade']) ? date("Y-m-d H:i:s") : $store['decade'];

            $store_data['category'] = $store['category'];

            $store_data['style_str'] = !empty($store['style'])?implode("", $store['style']):$store['style'];

            $store_data['style'] = serialize($store['style']);
            $store_data['zb_name'] = $store['zb_name'];
            $store_data['write_date'] = empty($store['write_date']) ? date("Y-m-d H:i:s") : $store['write_date'];
            $store_data['season'] = empty($store['season']) ? 0 : $store['season'];
            $store_data['user_id'] = $user_info['id'];
            $store_data['company_id'] = $company_id;
            $store_data['creation_time'] = date("Y-m-d H:i:s");
            $store_data['te_code'] = serialize($store['te_code']);

            var_dump($relate_info);
            $store_data['company_name'] = $relate_info['company_name'];
            if (empty($order_id)) {
                $order_id = StoreOrder::insertGetId($store_data);

            } else {
                StoreOrder::where('id', $order_id)->update($store_data);

            }
            $te_arr = [];
            foreach ($store['te_code'] as $val) {
                $te_arr = array_merge($te_arr, $val['te_code']);
            }
            $sys_te_arr = SystemTypeField::whereIn('id', $te_arr)->select('id', 'name')->get()->toArray();
            StoreTe::where('store_order_id', $order_id)->update(['status' => 1]);
            echo '*****^^^^';
            var_dump($order_id);
            foreach ($sys_te_arr as $k => $value) {
                StoreTe::insertGetId(['te_name' => $value['name'], 'te_id' => $value['id'], 'store_order_id' => $order_id, 'add_time' => date("Y-m-d H:i:s")]);
            }


            $table = $param['table'];
            foreach ($table as $k => $v) {
                foreach ($v as $sk => $sv) {
                    if (empty($sv)) {
                        unset($table[$k][$sk]);
                    }
                }
            }
            $files = $param['file'];
            $detail['store_order_id'] = $order_id;
            $detail['user_id'] = $user_info['id'];
            $detail['content'] = serialize($table);
            $detail['position_id'] = $relate_info['position_id'];
            $detail['department_id'] = $relate_info['department_id'];
            $detail['position_name'] = $relate_info['position_name'];
            $detail['department_name'] = $relate_info['department_name'];
            StoreDetail::where(['store_order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            $detail_res = StoreDetail::insertGetId($detail);


            StoreFile::where(['store_order_id' => $order_id])->update(['status' => 1]);

            foreach ($files as $k => $val) {

                if (empty($val['list'])) {
                    continue;
                }

                $file_type = empty($val['file_type']) ? 0 : $val['file_type'];
                $data['store_order_id'] = $order_id;

                foreach ($val['list'] as $fk => $fv) {

                    $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first();
                    if (empty($relate_info)) {
                        continue;
                    }
                    $relate_info = $relate_info->toArray();
                    $data['name'] = empty($fv['name']) ? "无" : $fv['name'];

                    $data['user_name'] = $fv['user_name'];
                    $company_id = $fv['company_id'];
                    $company_id = empty($company_id) ? 0 : $company_id;
                    $data['company_id'] = $company_id;
                    echo '**********company_name*********';
                    $com = UserCompany::where('id', $company_id)->first()->toArray();
                    var_dump($com);
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

                    $file_res = StoreFile::insertGetId($data);


                }


            }

            $data_zip['store_order_id'] = $order_id;
            $this->producer->produce(new StoreExportProducer($data_zip));


        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        return $this->success();
    }

    function createStoreold()
    {
        $d_res = 0;
        $p_res = 0;
        $file_res = 0;
        $detail_res = 0;
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
//        echo $user_info['id'].'&&&&&&'.$company_id;
//        echo CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toSql();
        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toArray();
        $store_data['store_no'] = $this->get_ordersn_big();

//        $redis = RedisUtil::getInstance();
        $order_id = empty($param['store_order_id']) ? 0 : $param['store_order_id'];

        try {
            Db::beginTransaction();
            $store = $param['form']['store_order'];

            if (empty($store['number']) || empty($store['sex']) || empty($store['decade']) || empty($store['category']) || empty($store['season']) || empty($store['style']) || empty($store['zb_name']) || empty($store['write_date']) || empty($store['te_code_arr'])) {
                throw new \Exception('参数缺失');
            } else {
                if (!is_array($store['te_code_arr'])) {
                    throw new \Exception('te_code参数错误');

                }
            }

            $store_data['number'] = $store['number'];
            $store_data['sex'] = $store['sex'];

            $store_data['decade'] = empty($store['decade']) ? date("Y-m-d") : $store['decade'];

            $store_data['category'] = $store['category'];
            $store_data['style'] = $store['style'];
            $store_data['zb_name'] = $store['zb_name'];
            $store_data['write_date'] = empty($store['write_date']) ? date("Y-m-d H:i:s") : $store['write_date'];
            $store_data['season'] = $store['season'];
            $store_data['user_id'] = $user_info['id'];
            $store_data['company_id'] = $company_id;
            $store_data['creation_time'] = date("Y-m-d H:i:s");
            $store_data['te_code'] = serialize($store['te_code']);

            var_dump($relate_info);
            $store_data['company_name'] = $relate_info['company_name'];
            if (empty($order_id)) {
                $order_id = StoreOrder::insertGetId($store_data);

            } else {
                StoreOrder::where('id', $order_id)->update($store_data);

            }
            $sys_te_arr = SystemTypeField::whereIn('id', $store['te_code_arr'])->select('id', 'name')->get()->toArray();
            StoreTe::where('store_order_id', $order_id)->update(['status' => 1]);
            echo '*****^^^^';
            var_dump($order_id);
            foreach ($sys_te_arr as $k => $value) {
                StoreTe::insertGetId(['te_name' => $value['name'], 'te_id' => $value['id'], 'store_order_id' => $order_id, 'add_time' => date("Y-m-d H:i:s")]);
            }


            $table = $param['table'];
            $files = $param['file'];
            $detail['store_order_id'] = $order_id;
            $detail['user_id'] = $user_info['id'];
            $detail['content'] = serialize($table);
            $detail['position_id'] = $relate_info['position_id'];
            $detail['department_id'] = $relate_info['department_id'];
            $detail['position_name'] = $relate_info['position_name'];
            $detail['department_name'] = $relate_info['department_name'];
            StoreDetail::where(['store_order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            $detail_res = StoreDetail::insertGetId($detail);


            StoreFile::where(['store_order_id' => $order_id])->update(['status' => 1]);

            foreach ($files as $k => $val) {

                if (empty($val['list'])) {
                    continue;
                }

                $file_type = empty($val['file_type']) ? 0 : $val['file_type'];
                $data['store_order_id'] = $order_id;

                foreach ($val['list'] as $fk => $fv) {

                    $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first();
                    if (empty($relate_info)) {
                        continue;
                    }
                    $relate_info = $relate_info->toArray();
                    $data['name'] = empty($fv['name']) ? "无" : $fv['name'];

                    $data['user_name'] = $fv['user_name'];
                    $company_id = $fv['company_id'];
                    $company_id = empty($company_id) ? 0 : $company_id;
                    $data['company_id'] = $company_id;
                    echo '**********company_name*********';
                    $com = UserCompany::where('id', $company_id)->first()->toArray();
                    var_dump($com);
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

                    $file_res = StoreFile::insertGetId($data);


                }


            }

            $data_zip['store_order_id'] = $order_id;
            $this->producer->produce(new StoreExportProducer($data_zip));


        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        return $this->success();
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
//        echo $user_info['id'].'&&&&&&'.$company_id;
//        echo CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toSql();
        $relate_info = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->first()->toArray();
//        $store_data['store_no'] = $this->get_ordersn_big();

//        $redis = RedisUtil::getInstance();
        $order_id = empty($param['store_order_id']) ? 0 : $param['store_order_id'];

        try {
            Db::beginTransaction();
            $store = $param['form']['store_order'];

            if (!isset($store['number']) || !isset($store['sex_name']) || !isset($store['decade']) || !isset($store['category']) || !isset($store['season']) || !isset($store['style']) || !isset($store['zb_name']) || !isset($store['write_date']) || !isset($store['te_code'])) {
                throw new \Exception('参数缺失');
            } else {
//                if (!is_array($store['te_code_arr'])) {
//                    throw new \Exception('te_code参数错误');
//
//                }
            }

            $store_data['number'] = $store['number'];
//            $store_data['sex'] = $store['sex_name'];
            $store_data['sex'] = empty($store['sex_name']) ? 1 : $store['sex_name'];

//            $store_data['decade'] = $store['decade'];
//            $store_data['decade'] = empty($store['decade'])?date("Y-m-d"):$store['decade'];
//            $store_data['decade'] = empty($store['decade']) ? date("Y") : date("Y", strtotime($store['decade']));
            $store_data['decade'] = empty($store['decade']) ? date("Y-m-d H:i:s") : $store['decade'];

            $store_data['category'] = $store['category'];
            $store_data['style_str'] = !empty($store['style'])?implode("", $store['style']):$store['style'];

            $store_data['style'] = serialize($store['style']);
            $store_data['zb_name'] = $store['zb_name'];
//            $store_data['write_date'] = $store['write_date'];
            $store_data['write_date'] = empty($store['write_date']) ? date("Y-m-d H:i:s") : $store['write_date'];

//            $store_data['season'] = $store['season'];
            $store_data['season'] = empty($store['season']) ? 0 : $store['season'];

            $store_data['user_id'] = $user_info['id'];
            $store_data['company_id'] = $company_id;
            $store_data['creation_time'] = date("Y-m-d H:i:s");
            $store_data['te_code'] = serialize($store['te_code']);

            var_dump($relate_info);
            $store_data['company_name'] = $relate_info['company_name'];
            if (empty($order_id)) {
                $order_id = StoreOrder::insertGetId($store_data);

            } else {
                StoreOrder::where('id', $order_id)->update($store_data);

            }
            $te_arr = [];
            foreach ($store['te_code'] as $val) {
                $te_arr = array_merge($te_arr, $val['te_code']);
            }
            $sys_te_arr = SystemTypeField::whereIn('id', $te_arr)->select('id', 'name')->get()->toArray();
            StoreTe::where('store_order_id', $order_id)->update(['status' => 1]);
            echo '*****^^^^';
            var_dump($order_id);
            foreach ($sys_te_arr as $k => $value) {
                StoreTe::insertGetId(['te_name' => $value['name'], 'te_id' => $value['id'], 'store_order_id' => $order_id, 'add_time' => date("Y-m-d H:i:s")]);
            }


            $table = $param['table'];
            foreach ($table as $k => $v) {
                foreach ($v as $sk => $sv) {
                    if (empty($sv)) {
                        unset($table[$k][$sk]);
                    }
                }
            }
            $files = $param['file'];
            $detail['store_order_id'] = $order_id;
            $detail['user_id'] = $user_info['id'];
            $detail['content'] = serialize($table);
            $detail['position_id'] = $relate_info['position_id'];
            $detail['department_id'] = $relate_info['department_id'];
            $detail['position_name'] = $relate_info['position_name'];
            $detail['department_name'] = $relate_info['department_name'];
            StoreDetail::where(['store_order_id' => $order_id, 'status' => 0])->update(['status' => 1]);
            $detail_res = StoreDetail::insertGetId($detail);


            StoreFile::where(['store_order_id' => $order_id])->update(['status' => 1]);

            foreach ($files as $k => $val) {

                if (empty($val['list'])) {
                    continue;
                }

                $file_type = empty($val['file_type']) ? 0 : $val['file_type'];
                $data['store_order_id'] = $order_id;

                foreach ($val['list'] as $fk => $fv) {

                    $relate_info = CompanyUserRelate::where(['user_id' => $fv['user_id'], 'company_id' => $fv['company_id']])->first();
                    if (empty($relate_info)) {
                        continue;
                    }
                    $relate_info = $relate_info->toArray();
                    $data['name'] = empty($fv['name']) ? "无" : $fv['name'];

                    $data['user_name'] = $fv['user_name'];
                    $company_id = $fv['company_id'];
                    $company_id = empty($company_id) ? 0 : $company_id;
                    $data['company_id'] = $company_id;
                    echo '**********company_name*********';
                    $com = UserCompany::where('id', $company_id)->first()->toArray();
                    var_dump($com);
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

                    $file_res = StoreFile::insertGetId($data);


                }


            }

            $data_zip['store_order_id'] = $order_id;
            $this->producer->produce(new StoreExportProducer($data_zip));


        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        return $this->success();
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

    function getIcon($store_order_info)
    {
        $edit = $this->iconStatus('api/store/saveDetail');
        $dis = $this->iconStatus('api/store/distributionList');
        $look = $this->iconStatus('api/store/info');
        $down = $this->iconStatus('api/store/down');

        $Incon_arr['edit'] = ['is_show' => $edit, 'name' => '编辑'];
        $Incon_arr['excel'] = ['is_show' => $dis, 'name' => '表单版本'];
        $Incon_arr['look'] = ['is_show' => $look, 'name' => '查看'];
        $Incon_arr['down'] = ['is_show' => $down, 'name' => '下载'];

        return $Incon_arr;


    }

    function getQuery($param)
    {

        return function ($q) use ($param) {
            if (!empty($param['number'])) {
                $q->where('number', $param['number']);

            }
            if (!empty($param['sex'])) {
                $q->where('sex', $param['sex']);

            }
            if (!empty($param['season'])) {
                $q->where('season', $param['season']);

            }
            if (!empty($param['decade'])) {
                $q->where('decade', $param['decade']);

            }
            if (!empty($param['category'])) {
                $q->where('category', $param['category']);

            }
            if (!empty($param['style'])) {
//                $q->where('style', $param['style']);
                $q->where('style_str', implode("", $param['style']));

            }
            if (!empty($param['te_arr'])) {
                $q->whereIn('id', $param['te_arr']);

            }
            if (!empty($param['company_id']) && empty($param['is_admin'])) {
                $q->where('company_id', $param['company_id']);

            }
        };


    }

    function list()
    {
//        $where = [];
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }
//        if(empty($company_id)){
//            return $this->error('个人用户不支持数据库使用');
//
//        }
        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $size = empty($param['size']) ? 10 : $param['size'];
        $te_order_arr = [];
        if (!empty($param['te_code_arr'])) {
            if (is_array($param['te_code_arr'])) {
                foreach ($param['te_code_arr'] as $value) {
                    $te_order_arr_p = StoreTe::where('te_id', $value)->pluck('store_order_id');
                    if (!empty($te_order_arr_p->toArray())) {
                        $te_order_arr = array_merge($te_order_arr, $te_order_arr_p->toArray());

                    } else {
//                        return $this->success('返回成功', []);
                    }
                }
                $te_order_arr = array_unique($te_order_arr);

            }
            if (empty($te_order_arr)) {
                return $this->success('返回成功', []);
            }
        }
//        var_dump($te_order_arr);
//        if(!empty($param['store_no'])){
//            $where[] = ['store_no',$param['store_no']];
//        }
//        if(!empty($param['number'])){
//            $where[] = ['number',$param['number']];
//        }
//        if(!empty($param['decade'])){
////            $where[] =[ 'decade','between',$param['decade']];
//        }
        $param['te_arr'] = $te_order_arr;
        $param['company_id'] = $company_id;
        $param['is_admin'] = $is_admin;
        $where = $this->getQuery($param);
        var_dump($param);
//            var_dump($where);
//        echo       StoreOrder::where($where)->whereIn('id',$te_order_arr)->select('id as store_order_id', 'store_no', 'user_id', 'number', 'sex', 'decade', 'category', 'season', 'style', 'zb_name', 'creation_time', 'company_id', 'company_name')->toSql();
//Db::enableQueryLog();
        $list = StoreOrder::where($where)->select('id as store_order_id', 'store_no', 'user_id', 'number', 'sex', 'decade', 'category', 'season', 'style', 'zb_name', 'creation_time', 'company_id', 'company_name', 'write_date')->orderByDesc('id')->paginate($size, ["*"], 'current');
//        var_dump(Arr::last(Db::getQueryLog()));
        $list = json_decode(json_encode($list), true);
        foreach ($list['data'] as $key => $val) {

            $te_arr_name = StoreTe::where(['store_order_id' => $val['store_order_id'], 'status' => 0])->select("te_name")->get()->toArray();
            if (!empty($te_arr_name)) {
                $list['data'][$key]['te_name'] = implode('、', array_column($te_arr_name, 'te_name'));
            }
            if (!empty($val['style'])) {
                $style = unserialize($val['style']);
                if (is_array($style)) {
                    switch ($style[0]) {
                        case 1:
                            $style[0] = '男装';
                            break;
                        case 2:
                            $style[0] = '女装';
                            break;
                        default :
                            $style[0] = '童装';

                    }
                }
                $list['data'][$key]['style'] = is_array($style)?implode("/", $style):$style;

            }
            switch ($val['sex']) {
                case 1:
                    $list['data'][$key]['sex_name'] = '男';
                    break;
                case 2:
                    $list['data'][$key]['sex_name'] = '女';
                    break;
                case 3:
                    $list['data'][$key]['sex_name'] = '儿童';
                    break;
                default:
                    $list['data'][$key]['sex_name'] = '未填写';
            }
            $list['data'][$key]['season'] = SystemTypeField::where('id', $val['season'])->value('name');

            $list['data'][$key]['down_url'] = \config('domain_url') . 'storage/file/store_order_zip/' . $val['store_order_id'] . '_store_order.zip';
            $list['data'][$key]['icon'] = $this->getIcon($val);
        }


        return $this->success('返回成功', $list);
    }


    function getDetail()
    {
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }

        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $param = $this->request->all();
        $order_id = empty($param['store_order_id']) ? 26 : $param['store_order_id'];
        $file = StoreFile::where(['store_order_id' => $order_id])->where(['status' => 0])->get()->toArray();
        $list = [];
        foreach ($file as $key => $value) {
            if ($value['file_type'] == 1) {

                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['file_type'] = $value['file_type'];
                $arr['upload_date'] = $value['upload_date'];
                $arr['user_id'] = $value['user_id'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];
            } else {
                $arr['upload_date'] = $value['upload_date'];
                $arr['file_type'] = $value['file_type'];
                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['user_id'] = $value['user_id'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];

            }
            $list[$value['file_type']]['file_type'] = $value['file_type'];
            $list[$value['file_type']]['list'][] = $arr;

        }
        $file = $list;
        $store_order = StoreOrder::where(['id' => $order_id])->select('decade', 'category', 'season', 'id as store_order_id', 'user_id', 'number', 'sex  as sex_name', 'style', 'zb_name', 'company_id', 'company_name', 'write_date', 'te_code')->orderByDesc('id')->first();

        if (!empty($store_order)) {
            $store_order = $store_order->toArray();
            $store_order['style'] = unserialize($store_order['style']);

            $store_order['te_code'] = unserialize($store_order['te_code']);
        }

        $form['store_order'] = $store_order;
        $order_detail = StoreDetail::where(['store_order_id' => $order_id, 'status' => 0])->first();

        if (!empty($order_detail->content)) {
            $order_detail->content = unserialize($order_detail->content);
        }
        $result['file'] = $file;
        $result['form'] = $form;
        $result['table'] = empty($order_detail->content) ? [] : $order_detail->content;

        return $this->success('详情返回成功', $result);


    }

    function info()
    {
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }

        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $param = $this->request->all();
        $order_id = empty($param['store_order_id']) ? 26 : $param['store_order_id'];
        $file = StoreFile::where(['store_order_id' => $order_id])->where(['status' => 0])->get()->toArray();
        $list = [];
        foreach ($file as $key => $value) {
            if ($value['file_type'] == 1) {

                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['file_type'] = $value['file_type'];
                $arr['upload_date'] = $value['upload_date'];
                $arr['user_id'] = $value['user_id'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];
            } else {
                $arr['upload_date'] = $value['upload_date'];
                $arr['file_type'] = $value['file_type'];
                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['user_id'] = $value['user_id'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];

            }
            $list[$value['file_type']]['file_type'] = $value['file_type'];
            $list[$value['file_type']]['list'][] = $arr;

        }
        $file = $list;
        $store_order = StoreOrder::where(['id' => $order_id])->orderByDesc('id')->first();

        if (!empty($store_order)) {
            $store_order = $store_order->toArray();
            $store_order['style'] = unserialize($store_order['style']);
            $store_order['te_code'] = unserialize($store_order['te_code']);
        }

        $form['store_order'] = $store_order;
        $order_detail = StoreDetail::where(['store_order_id' => $order_id, 'status' => 0])->first();

        if (!empty($order_detail->content)) {
            $order_detail->content = unserialize($order_detail->content);
        }
        $result['file'] = $file;
        $result['form'] = $form;
        $result['table'] = empty($order_detail->content) ? [] : $order_detail->content;

        return $this->success('详情返回成功', $result);


    }

    function info1()
    {
        $param = $this->request->all();
        $order_id = empty($param['store_order_id']) ? 26 : $param['store_order_id'];
        $file = StoreFile::where(['store_order_id' => $order_id])->where(['status' => 0])->get()->toArray();
        $list = [];
        foreach ($file as $key => $value) {
            if ($value['file_type'] == 1) {

                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['file_type'] = $value['file_type'];
                $arr['upload_date'] = $value['upload_date'];
                $arr['user_id'] = $value['user_id'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];
            } else {
                $arr['upload_date'] = $value['upload_date'];
                $arr['file_type'] = $value['file_type'];
                $arr['url'] = $value['file_url'];
                $arr['user_name'] = $value['user_name'];
                $arr['user_id'] = $value['user_id'];
                $arr['company_id'] = $value['company_id'];
                $arr['name'] = $value['name'];
                $arr['file_id'] = $value['id'];

            }
            $list[$value['file_type']]['file_type'] = $value['file_type'];
            $list[$value['file_type']]['list'][] = $arr;

        }
        $file = $list;
        $store_order = StoreOrder::where(['id' => $order_id])->orderByDesc('id')->first();

        if (!empty($store_order)) {
            $store_order = $store_order->toArray();
        }

        $form['store_order'] = $store_order;
        $order_detail = StoreDetail::where(['store_order_id' => $order_id, 'status' => 0])->first();

        if (!empty($order_detail->content)) {
            $order_detail->content = unserialize($order_detail->content);
        }
        $result['file'] = $file;
        $result['form'] = $form;
        $result['table'] = empty($order_detail->content) ? [] : $order_detail->content;

        return $this->success('详情返回成功', $result);


    }


    /**
     * 订单详情分配表单---开始分配
     */


    function distributionField()
    {
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }

        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $company_id = $this->request->switch_company;
        $param = $this->request->all();
        if (empty($param['store_order_id']) || empty($param['version_id']) || empty($param['type'])) {
            return $this->error('参数缺失');
        }
        $data['version_num'] = CompanyFieldSet::where('id', $param['version_id'])->value('version_num');

        $data['version_id'] = $param['version_id'];
        $data['store_order_id'] = $param['store_order_id'];
        $data['type'] = $param['type'];
        $data['company_id'] = $company_id;

        try {
            Db::beginTransaction();
            StoreFieldVersion::where(['store_order_id' => $param['store_order_id'], 'type' => $param['type']])->update(['status' => 1]);
//            UserOrder::where(['id' => $param['order_id']])->update($update_date);
            StoreFieldVersion::insertGetId($data);
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('分配成功');


    }

    /**
     * 当前订单 可分配的表单版本
     */
    function distributionList()
    {
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }

        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $param = $this->request->all();
        if (empty($param['store_order_id'])) {
            return $this->error('参数缺失');
        }
        $where['company_id'] = $company_id;
        $where['status'] = 0;

        $list = CompanyFieldSet::where($where)->select('version_num', 'type', 'id as version_id')->get()->toArray();


        $list = array_unique($list, SORT_REGULAR);
        $list = array_values($list);
        $versiom_arr = StoreFieldVersion::where(['store_order_id' => $param['store_order_id'], 'status' => 0])->select('version_num', 'version_id')->get()->toArray();
        $v_arr = array_column($versiom_arr, 'version_id');
        foreach ($list as $key => $val) {
            if (in_array($val['version_id'], $v_arr)) {
                $list[$key]['status'] = 1;
            } else {
                $list[$key]['status'] = 0;

            }
        }
        $result = [];

        foreach ($list as $value) {
            $arr[$value['type']][] = $value;
        }
        $arr[1] = empty($arr[1]) ? [] : $arr[1];
        $arr[2] = empty($arr[2]) ? [] : $arr[2];

        $arr[3] = empty($arr[3]) ? [] : $arr[3];

        $result[] = ['name' => '设计师', 'child' => $arr[1]];
        $result[] = ['name' => '制版', 'child' => $arr[2]];

        $result[] = ['name' => '制样', 'child' => $arr[3]];

//        var_dump($list);
//
//        var_dump(Arr::last(Db::getQueryLog()));

        return $this->success('返回成功', $result);


    }


    /**
     * 详情各表单字段列表
     */
    function getdistributionField()
    {
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $is_admin = $user_info['is_admin'];
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_' . $user_info['id']);
        if (empty($company_id) && empty($is_admin)) {
            return $this->error('个人用户不支持数据库使用');
        }

        if (empty($level) && empty($is_admin)) {
            return $this->error('公司该服务到期请续费，方可继续使用');

        }
        $param = $this->request->all();
        if (empty($param['store_order_id'])) {
            return $this->error('参数缺失');
        }
        $where_version = [];

        $where_version['status'] = 0;


        $dv_list = StoreFieldVersion::where(['store_order_id' => $param['store_order_id']])->where($where_version)->get()->toArray();
//        var_dump($dv_list);
//        $where['company_id'] = $company_id;
//        foreach ($dv_list as $k => $v) {
//            $where['version_num'] = $v['version_num'];
//            $where['type'] = $v['type'];
//            $where['status'] = 0;
//            var_dump($where);
//            $dv_list[$k]['filed_list'] = CompanyFieldSet::where($where)->select('field_name', 'field_name_py')->get()->toArray();
//        }
        foreach ($dv_list as $k => $v) {
            $where['version_id'] = $v['version_id'];
//            $where['type'] = $v['type'];
            $where['status'] = 0;
//            var_dump($where);
//            $dv_list[$k]['filed_list'] = CompanyFieldSet::with(['versionField'=>function($query){
//                $query->where('status',0);
//            }])->where($where)->select('id', 'version_num')->get()->toArray();
            $dv_list[$k]['filed_list'] = VersionField::where($where)->select('field_name', 'id as field_name_py')->get()->toArray();
        }


        return $this->success('请求成功', $dv_list);


    }

}