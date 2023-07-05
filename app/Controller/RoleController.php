<?php


namespace App\Controller;

use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Common\Controller\BaseController;
use App\Model\AuthRole;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Service\MenuService;
use Hyperf\Config\Config;
use App\Service\RoleService;
use Hyperf\HttpServer\Annotation\Middleware;

use Hyperf\HttpServer\Annotation\Middlewares;
use App\Middleware\Auth\CheckToken;


/**
 * Class MenuController
 * @package App\Controller
 * @AutoController(prefix="/api/role")
 * @Middlewares({
 *  @Middleware(CheckToken::class)
 * })
 */
class RoleController extends BaseController
{
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var RoleService
     */
    private $roleService;

    public function addRole(RequestInterface $request)
    {
        $param = $request->all();
        $company_id = $request->switch_company;
        if (empty($param['role_name']) || empty($param['role_remark'])) {
            return $this->error('参数缺失', []);
        }
        $role['name'] = $param['role_name'];
        $role['remark'] = $param['role_remark'];
        $role['company_id'] = $company_id;
        $role['is_department'] = empty($param['is_department'])?0:1;
        if(empty($param['rule_ids'])){
            $rule_arr = [];
        }else{
            $rule_ids = $param['rule_ids'];
            $rule_arr = explode(',', $rule_ids);
        }
        $role['status'] = 1;
        $result = $this->roleService->addRole($role, $rule_arr);
        if (!empty($result)) {
            return $this->success();
        } else {
            return $this->error();
        }


    }


    public function editRole(RequestInterface $request)
    {
        $param = $request->all();
        $company_id = $request->switch_company;

        if (empty($param['role_name']) || empty($param['role_remark']) || empty($param['role_id']) ) {
            return $this->error('参数缺失', []);
        }
        $role['name'] = $param['role_name'];
        $role['remark'] = $param['role_remark'];
        $role['role_id'] = $param['role_id'];
        $role['company_id'] = $company_id;

        $role['is_department'] = empty($param['is_department'])?0:1;

        if(empty($param['rule_ids'])){
            $rule_arr = [];
        }else{
            $rule_ids = $param['rule_ids'];
            $rule_arr = explode(',', $rule_ids);
        }



//        $rule_ids = $param['rule_ids'];
//        $rule_arr = explode(',', $rule_ids);
        $result = $this->roleService->editRole($role, $rule_arr);
        if (!empty($result)) {
            return $this->success();
        } else {
            return $this->error();
        }


    }


    function roleInfo(RequestInterface $request,ResponseInterface $response)
    {
        $param = $request->all();
        $role_id = $param['role_id'];
        if (empty($role_id)) {
            return $this->error();
        }
        $company_id = $request->switch_company;
        $rules = AuthRoleRule::query()->where('role_id', $role_id)->pluck("rule_id");
        $role_info = AuthRole::query()->where(['id'=>$role_id,'company_id'=>$company_id])->first();
        if(empty($role_info)){
            return $response->json(['code' => 101, 'message' => '切换跳转', 'data' => []]);

        }
        $role_info = $role_info->toArray();
        $data['role_info'] = $role_info;
        $data['role_rule_list'] = $rules;
        return $this->success('请求成功',$data);
    }

    function roleList(RequestInterface $request){
        $param = $request->all();
        $company_id = $request->switch_company;
        $user_info = $this->request->user_info;
        $where = [];
        $size = empty($param['size'])?10:$param['size'];
        if(!empty($param['name'])){
            $where['name'] = $param['name'];
        }

        $where['company_id'] = $company_id;
        $where['status'] = 1;
//       $list =  AuthRole::query()->where($where)->orWhere('yu_type','>',0)->paginate($size,["*"],"current");
        $list =  AuthRole::query()->where($where)->paginate($size,["*"],"current");

        $list = json_decode(json_encode($list),true);
       $list['is_admin'] = $user_info['is_admin'];
       return $this->success('返回成功',$list);
    }



    function delRole(RequestInterface $request){
        $param = $request->all();
        $role_id = $param['role_id'];
        $info = AuthRole::where(['id'=>$role_id])->first();
        if($info->name =='超级管理员'){
            return $this->error('不可删除最高权限角色');
        }
        if(is_array($role_id)){
            foreach ($role_id as $v){
                AuthRole::where(['id'=>$v])->delete();

            }
        }else{
            AuthRole::where(['id'=>$role_id])->delete();
        }

        return $this->success();



    }


    function adminRoleList(RequestInterface $request){
        $param = $request->all();
        $where = [];
        $company_id = $request->switch_company;
        $where['company_id'] = $company_id;
        $where['is_company_admin'] = 0;
        $where['type'] = 0;
//        if(!empty($param['name'])){
//            $where['name'] = $param['name'];
//        }
        $where['is_department'] = 0;
        $list =  AuthRole::query()->where($where)->get()->toArray();
        return $this->success('返回成功',$list);
    }


    function setCompayRole(){
        $param = $this->request->all();
        if(empty($param['role_id'])){
            return $this->error('参数缺失');
        }

        AuthRole::update(['is_company_admin'=>0]);
        AuthRole::where(['id'=>$param['role_id']])->update(['is_company_admin'=>1]);

        return $this->success('操作成功');

    }


    function companyRole(){
        $company_id = $this->request->switch_company;
        $where['company_id'] = $company_id;
        $where['status'] = 1;
//        var_dump($where);
//        echo AuthRole::where($where)->select('id as role_id','name as role_name','type','company_id')->toSql();

        $role_list = AuthRole::where($where)->select('id as role_id','name as role_name')->get()->toArray();
//        $role_list1 = AuthRole::where(['type'=>1,'status'=>1])->select('id as role_id','name as role_name')->get()->toArray();
//        $role_list = array_merge($role_list,$role_list1);
        return $this->success('返回成功',$role_list);

    }

    function alreadyRole(){
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        if($user_info['is_admin'] != 1){
            return  $this->error('预设权限仅管理员有此权限');
        }
        if(empty($param['role_id']) ||  empty($param['position_type'])){
            return $this->error('参数缺失');
        }
        $yu = '';
//        switch ($param['position_type']){
//            case 1:
//                $yu='设计师';
//                break;
//            case 2:
//                $yu='制版师';
//                break;
//            case 3:
//                $yu='制样师';
//                break;
//            case 4:
//                $yu='管理元';
//                break;
//            default:
//                $yu = '无';
//        }
        $yu = $param['position_type'];
        if($param['position_type'] ==4){
            $role_id = $param['role_id'];

            AuthRole::where(['is_company_admin'=>1])->update(['is_company_admin'=>0,'yu_type'=>0,'type'=>0,'position_type'=>0]);

//            AuthRole::where('id','!=',$role_id)->update(['is_company_admin'=>0,'yu_type'=>0]);

            AuthRole::where('id',$role_id)->update(['is_company_admin'=>1,'yu_type'=>$yu]);
        }else{
            $role_id = $param['role_id'];
            AuthRole::where(['type'=> 1,'position_type'=>$param['position_type'],'yu_type'=>$yu])->update(['type'=> 0,'position_type'=>0,'yu_type'=>0,'is_company_admin'=>0]);

            AuthRole::where('id',$role_id)->update(['type'=> 1,'position_type'=>$param['position_type'],'yu_type'=>$yu]);
        }

        return $this->success();




    }
    function setCompanyAdmin(){
        $param = $this->request->all();
        if(empty($param['role_id'])){
            return $this->error('参数缺失');
        }
        $role_id = $param['role_id'];
        AuthRole::update(['is_company_admin'=>0]);

        AuthRole::where('id',$role_id)->update(['is_company_admin'=>1]);
        return $this->success();




    }





}