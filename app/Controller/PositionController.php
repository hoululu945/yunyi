<?php


namespace App\Controller;
use App\Common\Controller\BaseController;
use App\Common\Tool\qiniu;
use App\Common\Tool\QrCodeProduce;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRole;
use App\Model\AuthRoleRelate;
use App\Model\CompanyUserRelate;
use App\Model\Department;
use App\Model\Position;
use App\Model\User;
use App\Model\UserCompany;
use App\Model\UserInfo;
use App\Model\UserLoginLog;
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
use App\Service\LoginService;

/**
 * @AutoController(prefix="/api/position")
 * @Middlewares({
 *     @Middleware(CheckToken::class)
 * })
 */

class PositionController extends  BaseController
{
    function addPosition(){
        $param = $this->request->all();
        $company_id = $this->request->switch_company;
        $company_name = UserCompany::where('id',$company_id)->value('company_name');
        var_dump($param);
        if(empty($param['department_id']) || empty($param['position_name'])){
            return $this->error('参数缺失');
        }
        $data['company_id'] = $company_id;
        $data['company_name'] = $company_name;
        $data['department_name'] = Department::where('id',$param['department_id'])->value('department_name');
        $data['add_time'] = date("Y-m-d H:i:s");
        $data['position_name'] = $param['position_name'];
        $data['department_id'] = $param['department_id'];
        Position::insert($data);
        return $this->success('添加成功');
    }
    function editPosition(){
        $param = $this->request->all();
        $company_id = $this->request->switch_company;
        $company_name = UserCompany::where('id',$company_id)->value('company_name');
        if(empty($param['department_id']) || empty($param['position_name']) || empty($param['position_id'])){
            return $this->error('参数缺失');
        }
        $data['company_id'] = $company_id;
        $data['company_name'] = $company_name;
        $data['department_name'] = Department::where('id',$param['department_id'])->value('department_name');
        $data['add_time'] = date("Y-m-d H:i:s");
        $data['position_name'] = $param['position_name'];
        $data['department_id'] = $param['department_id'];
        Position::where('id',$param['position_id'])->update($data);
        return $this->success('编辑成功');
    }


    function info(){
        $param = $this->request->all();

        if(empty($param['position_id'])){
            return $this->error('参数缺失');
        }
        $info = Position::where('id',$param['position_id'])->first();
        return  $this->success('返回成功',$info);
    }


    function list(){
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;

        $where = [];
        $where['company_id'] = $company_id;
        $size = empty($param['size']) ? 10 : $param['size'];
        $where['status'] = 0;
        $list = Position::where($where)->paginate($size,['*'],'current');
        return $this->success('返回成功',$list);





    }
    function del(){
        $param = $this->request->all();
        if(empty($param['position_id'])){
            return $this->error('参数缺失');
        }

        Position::where('id',$param['position_id'])->update(['status'=>1]);
        return  $this->success('删除成功');
    }


    function getPosition(){
        $param = $this->request->all();
        $company_id = $this->request->switch_company;
        if(empty($param['department_id'])){
            return $this->error('参数缺失');
        }

        $list = Position::where(['company_id'=>$company_id,'department_id'=>$param['department_id']])->get()->toArray();
        return $this->success('请求成功',$list);










    }


}