<?php


namespace App\Controller;

use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Common\Controller\BaseController;
use App\Common\Tool\HyberfRedis;
use App\Common\Tool\ImgeDown;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use App\Model\CompanyFieldSet;
use App\Model\CompanyUserRelate;
use App\Model\OrderFieldVersion;
use App\Model\StyleParam;
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
use Hyperf\Utils\Arr;


//

/**
 * Class MenuController
 * @package App\Controller
 * @AutoController(prefix="/api/field")
 * @Middlewares({
 *  @Middleware(CheckToken::class)
 * })
 */
class FieldController extends BaseController
{


    function addField()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
//        $param['field_name_arr'] = ['kan', 'ting', 'jiang', 'wen'];
        if (empty($param['field_name_arr']) || empty($param['type']) || empty($param['version_num'])) {
            return $this->error('参数缺失');
        }
        $company_name = UserCompany::where('id', $company_id)->value('company_name');

//        $data['field_name_py'] = $param['field_name_py'];
        $data['add_time'] = date('Y-m-d H:i:s');
        $data['company_id'] = $company_id;
        $data['type'] = $param['type'];
        $data['version_num'] = $param['version_num'];
        $data['user_id'] = $user_info['id'];
        $data['user_name'] = $user_info['userName'];
        $data['company_name'] = $company_name;
        try {
            $vdata['version_id'] = CompanyFieldSet::insertGetId($data);

            foreach ($param['field_name_arr'] as $val) {
                $vdata['field_name'] = $val;
                VersionField::insert($vdata);

            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success('添加成功');


    }

    function editField()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
//        $param['field_name_arr'] = ['kan1', 'ting2', 'jiang1', 'wen2'];

        if (empty($param['field_id']) || empty($param['field_name_arr'])  || empty($param['type']) || empty($param['version_num'])) {
            return $this->error('参数缺失');
        }
        $company_name = UserCompany::where('id', $company_id)->value('company_name');
//        $data['field_name'] = $param['field_name'];
//        $data['field_name_py'] = $param['field_name_py'];
//        $data['add_time'] = date('Y-m-d H:i:s');
        $data['company_id'] = $company_id;
        $data['type'] = $param['type'];
        $data['version_num'] = $param['version_num'];
        $data['user_id'] = $user_info['id'];
        $data['user_name'] = $user_info['userName'];
        $data['company_name'] = $company_name;

        try {
            CompanyFieldSet::where('id', $param['field_id'])->update($data);
            VersionField::where('version_id',$param['field_id'])->update(['status'=>1]);
            $vdata['version_id'] = $param['field_id'];
            foreach ($param['field_name_arr'] as $val) {
                $vdata['field_name'] = $val;
                VersionField::insertGetId($vdata);

            }
//            CompanyFieldSet::where('id', $param['field_id'])->update($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success('更新成功');


    }


    function info()
    {
        $param = $this->request->all();
        if (empty($param['field_id'])) {
            return $this->error('参数缺失');
        }
//        $info = CompanyFieldSet::with(['versionField'=>function($query){
//            $query->where('status',0);
//        }])->where('id', $param['field_id'])->first();
        $info = CompanyFieldSet::where('id', $param['field_id'])->first();
        $field_name = VersionField::where(['version_id'=>$info->id,'status'=>0])->pluck('field_name');
        $info->field_name_arr = $field_name;
        var_dump($info);
        return $this->success('返回成功', $info);

    }

    function list()
    {
        $param = $this->request->all();
        $where = [];
        $size = empty($param['size']) ? 10 : $param['size'];
        $company_id = $this->request->switch_company;
        $where['company_id'] = $company_id;
        if (!empty($param['version_num'])) {
            $where['version_num'] = $param['version_num'];
        }
        if (!empty($param['type'])) {
            $where['type'] = $param['type'];
        }
        $where['status'] = 0;
//        var_dump($where);
        $list = CompanyFieldSet::with(['versionField'=>function($query){
            $query->where('status',0);
        }])->where($where)->orderByDesc('id')->paginate($size, ["*"], 'current');
        return $this->success('返回成功', $list);
    }

    function del()
    {
        $param = $this->request->all();
        if (empty($param['field_id'])) {
            return $this->error('参数缺失');
        }

        CompanyFieldSet::where('id', $param['field_id'])->update(['status' => 1]);
        return $this->success('删除成功');


    }

    /**
     * 当前订单 可分配的表单版本
     */
    function distributionList()
    {
        $user_info = $this->request->user_info;
//        var_dump($user_info);
        $company_id = $this->request->switch_company;
        $param = $this->request->all();
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }
        $where['company_id'] = $company_id;
        $where['status'] = 0;
        $order_info = UserOrder::where('id',$param['order_id'])->first();
        $group_info = UserOrderGroup::where(['user_id' => $user_info['id'], 'order_id' => $param['order_id']])->first();
        if(empty($group_info)){
            return $this->error('未获取到订单信息');
        }
//        Db::enableQueryLog();
        if ($group_info->type == 1) {
            if($order_info->is_send==1){
                return $this->error('当前订单已发派出去,内部循环终止，需求方不可再分配表单版本');
            }
//            $where['type'] = 1;
//            $list = CompanyFieldSet::where($where)->select('version_num', 'type','id as field_id')->get()->toArray();

        } else {
//            $list = CompanyFieldSet::where($where)->select('version_num', 'type')->whereIn('type', [2, 3])->get()->toArray();

        }
        $list = CompanyFieldSet::where($where)->select('version_num', 'type','id as version_id')->get()->toArray();


        $list = array_unique($list, SORT_REGULAR);
//        $list = json_decode(json_encode($list),true);
        $list = array_values($list);
        $versiom_arr = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->select('version_num','version_id')->get()->toArray();
        $v_arr = array_column($versiom_arr, 'version_id');
        foreach ($list as $key => $val) {
            if (in_array($val['version_id'], $v_arr)) {
                $list[$key]['status'] = 1;
            } else {
                $list[$key]['status'] = 0;

            }
        }
        $result = [];
        $result = [];

        foreach ($list as $value){
            $arr[$value['type']][]= $value;
        }
        $arr[1] = empty($arr[1])?[]:$arr[1];
        $arr[2] = empty($arr[2])?[]:$arr[2];

        $arr[3] = empty($arr[3])?[]:$arr[3];

        $result[] = ['name'=>'设计师','child'=> $arr[1]];
        $result[] = ['name'=>'制版','child'=> $arr[2]];

        $result[] = ['name'=>'制样','child'=> $arr[3]];

//        var_dump($list);
//
//        var_dump(Arr::last(Db::getQueryLog()));

        return $this->success('返回成功', $result);


    }

    /**
     * 订单详情分配表单---开始分配
     */


    function distributionField()
    {
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $param = $this->request->all();
        if (empty($param['order_id']) || empty($param['version_id']) || empty($param['type'])) {
            return $this->error('参数缺失');
        }
        $data['version_num'] = CompanyFieldSet::where('id',$param['version_id'])->value('version_num');
        $data['version_id'] = $param['version_id'];
        $data['order_id'] = $param['order_id'];
        $data['type'] = $param['type'];
        $data['company_id'] = $company_id;
        $type = UserOrderGroup::where(['order_id' => $param['order_id'], 'user_id' => $user_info['id']])->value('type');
        $group_info = UserOrderGroup::where(['order_id' => $param['order_id'], 'user_id' => $user_info['id']])->first();
        $order_info = UserOrder::where('id',$param['order_id'])->first();
        $redis = RedisUtil::getInstance();
        $level = $redis->getKeys('level_userid_'.$user_info['id']);
        if(($level<1) || ($level ==1 && $order_info['is_send']==1 )  || ($level>1 && $group_info->type==1 && $order_info['is_send']==1)){
            return $this->error('不具有该操作权限');
        }
        if ($group_info->type == 1) {
            if ($order_info->is_send == 1) {
                return $this->error('当前订单已发派出去,内部循环终止，需求方不可再分配表单版本');
            }
        }
//        if ($type == 1) {
//            $update_date = ['is_distribution' => 1];
//        } else {
//            $update_date = ['is_service_distribution' => 1];

//        }
//        $update_date['is_service_distribution'] = 1;
//        $update_date['is_distribution'] = 1;
        try {
            Db::beginTransaction();
            OrderFieldVersion::where(['order_id' => $param['order_id'], 'type' => $param['type']])->update(['status' => 1]);
//            UserOrder::where(['id' => $param['order_id']])->update($update_date);
            OrderFieldVersion::insertGetId($data);
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }

        Db::commit();
        return $this->success('分配成功');


    }

    /**
     * 详情各表单字段列表
     */
    function getdistributionField()
    {
//        $arr = [['id'=>1,'name'=>'as'],['id'=>1,'name'=>'fff']];
//        $a = array_column($arr,null,'id');
//        var_dump($a);
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
        $param = $this->request->all();
        if (empty($param['order_id'])) {
            return $this->error('参数缺失');
        }


        $dv_list = OrderFieldVersion::where(['order_id' => $param['order_id'], 'status' => 0])->get()->toArray();
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
            $dv_list[$k]['filed_list'] = VersionField::where($where)->select('field_name','id as field_name_py')->get()->toArray();
        }


        return $this->success('请求成功', $dv_list);


    }


}