<?php


namespace App\Controller;

use App\Common\Controller\BaseController;
use App\Common\Tool\ExcelBom;
use App\Common\Tool\HyberfRedis;
use App\Common\Tool\ImgeDown;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use App\Model\CompanyUserRelate;
use App\Model\StyleParam;
use App\Model\SystemType;
use App\Model\SystemTypedel;
use App\Model\SystemTypeField;
use App\Model\User;
use App\Model\UserCompany;
use App\Model\UserOrder;
use App\Model\UserOrderDiscuss;
use App\Model\UserOrderFile;
use App\Model\UserOrderGroup;
use App\Model\UserOrderTalk;
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


//

/**
 * Class MenuController
 * @package App\Controller
 * @AutoController(prefix="/api/system")
 * @Middlewares({
 *  @Middleware(CheckToken::class)
 * })
 */
class SystemController extends BaseController
{


    function list()
    {
        $param = $this->request->all();
        $where = [];
        $size = empty($param['size']) ? 10 : $param['size'];
        if (!empty($param['type_name'])) {
            $where['type_name'] = $param['type_name'];
        }
//        $where['status'] = 0;
//        $list = SystemTypeField::where($where)->select('id', 'type', 'type_name', 'add_time', 'is_te')->orderByDesc('id')->paginate($size, ['*'], 'current');
        $list = SystemType::where($where)->select('id', 'type', 'type_name', 'add_time', 'is_te')->orderByDesc('id')->paginate($size, ['*'], 'current');

        return $this->success('返回成功', $list);


    }

    function pyList()
    {
        $param = $this->request->all();
        $where = [];
        if (!empty($param['type'])) {
            $where['type'] = $param['type'];
        } else {
            return $this->error('参数缺失');
        }
        $where['status'] = 0;
        $list = SystemTypeField::where($where)->orderByDesc('id')->get()->toArray();
        return $this->success('返回成功', $list);


    }

    function addType()
    {
        $param = $this->request->all();
//        $param['name_arr'] = ['冬梅', '夏荷'];
        if (empty($param['type']) || empty($param['type_name']) || empty($param['name_arr'])) {
            return $this->error('参数缺失');
        }
        $pdata['type'] = $data['type'] = $param['type'];
        $pdata['type_name'] = $data['type_name'] = $param['type_name'];
//        $data['name'] = $param['name'];
        if (!empty($param['is_te'])) {
            $pdata['is_te'] = $data['is_te'] = $param['is_te'];
        }

        try {
            $sid = SystemType::insertGetId($pdata);
            $data['system_type_id'] = $sid;
//            SystemTypeField::where('type',$param['type'])->update(['status'=>1]);
            foreach ($param['name_arr'] as $v) {
                $data['name'] = $v;
                SystemTypeField::insertGetId($data);

            }
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
        return $this->success();


    }

    function info()
    {
        $param = $this->request->all();
        if (empty($param['type_id'])) {
            return $this->error('参数缺失');
        }
//        $info = SystemType::where('id',$param['type_id'])->first();
//        $info = SystemType::with(['typeField'=>function($query){
//            $query->select('name');
//        }])->select('id','type','type_name','add_time','is_te')->where('id',$param['type_id'])->first();
        $info = SystemType::where('id', $param['type_id'])->select('id', 'type', 'type_name', 'add_time', 'is_te')->first()->toArray();
        $list = SystemTypeField::where('system_type_id', $info['id'])->where('status',0)->pluck('name')->toArray();
        $info['name_arr'] = $list;
        return $this->success('返回成功', $info);
    }

    function editType()
    {
        $param = $this->request->all();
//        $param['name_arr'] = ['冬梅', '夏荷'];
        if (empty($param['type']) || empty($param['type_name']) || empty($param['name_arr']) || empty($param['type_id'])) {
            return $this->error('参数缺失');
        }
        $pdata['type'] = $data['type'] = $param['type'];
        $pdata['type_name'] = $data['type_name'] = $param['type_name'];
//        $data['name'] = $param['name'];
        if (!empty($param['is_te'])) {
            $pdata['is_te'] = $data['is_te'] = $param['is_te'];
        }

        try {
            $sid = SystemType::where('id',$param['type_id'])->update($pdata);
            $data['system_type_id'] = $param['type_id'];
            SystemTypeField::where('system_type_id',$param['type_id'])->update(['status'=>1]);
            foreach ($param['name_arr'] as $v) {
                $data['name'] = $v;
                SystemTypeField::insertGetId($data);

            }
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
        return $this->success();

    }

    function editTypeold()
    {
        $param = $this->request->all();
        if (empty($param['type']) || empty($param['type_name']) || empty($param['name']) || empty($param['type_id'])) {
            return $this->error('参数缺失');
        }
        $data['type'] = $param['type'];
        $data['type_name'] = $param['type_name'];
        $data['name'] = $param['name'];
        if (!empty($param['is_te'])) {
            $data['is_te'] = $param['is_te'];
        } else {
            $data['is_te'] = 0;

        }
        try {
            SystemType::where('id', $param['type_id'])->update($data);
        } catch (\Exception $exception) {

        }
        return $this->success();


    }


    function delType()
    {

        $param = $this->request->all();
        if (empty($param['type_id'])) {
            return $this->error('参数缺失');
        }
        SystemType::where('id', $param['type_id'])->delete();
        return $this->success('删除成功');
    }

    function getBomUrl()
    {
        $ex = new ExcelBom();
        $url = $ex->export();
        return $this->success('返回成功', $url);
    }


    function teCode()
    {
        $param = $this->request->all();
       $system_type_id_arr =  SystemType::where(['is_te'=>1])->pluck('id');
        $te_arr = SystemTypeField::where(['is_te' => 1])->whereIn('system_type_id',$system_type_id_arr)->get()->toArray();
        $list = [];
        foreach ($te_arr as $k => $v) {
//            $list[$v['type_name']][] = $v['name'];
            $arr['name'] = $v['name'];
            $arr['id'] = $v['id'];
//            $list[$v['type_name']][] = $v['name'];
            $list[$v['type_name']][] = $arr;


        }
        return $this->success('返回成功', $list);
    }


    function dd()
    {
//        $da = SystemType::get()->toArray();
//        $list = [];
//        foreach ($da as $v){
//            $list[$v['type_name']] = $v;
////            echo SystemType::where('id','!=',$id)->where('type_name',$v['type_name'])->delete();
//        }
////        var_dump($list);
//        foreach ($list as $vv){
//            unset($vv['id']);
//            SystemTypedel::insert($vv);
//
//        }
        $list = SystemTypedel::get()->toArray();
        foreach ($list as $v) {
            SystemTypeField::where('type', $v['type'])->update(['system_type_id' => $v['id']]);
        }
    }


}