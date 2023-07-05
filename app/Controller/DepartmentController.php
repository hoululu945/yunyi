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
 * @AutoController(prefix="/api/department")
 * @Middlewares({
 *     @Middleware(CheckToken::class)
 * })
 */
class DepartmentController extends BaseController
{

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var MenuService
     */
    private $menuService;

    function add()
    {
        $data = $this->request->all();
        if (empty($data['name'])) {
            return $this->error('参数缺失');
        }
        $company_id = $this->request->switch_company;
        $company_name = UserCompany::where('id', $company_id)->value('company_name');
        foreach ($data as $key => $val) {
            if (is_null($val)) {
                unset($data[$key]);
            }
        }
//        !isset($data['parent_id']) && $data['parent_id'] = 0;
        $is_yuan = Department::where('id', $data['parent_id'])->value('parent_id');
        if (!empty($is_yuan)) {
            return $this->error('不允许添加员工');
        }
        !isset($data['sort']) && $data['sort'] = 100;
        if (empty($data['parent_id'])) {
            $data['type'] = 1;
        } else {
            $data['type'] = 2;

        }
        !isset($data['name']) && $data['name'] = '';
        $data['company_id'] = $company_id;
        $data['company_name'] = $company_name;


        if (isset($data['id']) && $data['id'] > 0) {
            $return = $this->menuService->editDepartment($data['id'], $data);
            var_dump($return);
        } else {
            $return = $return = $this->menuService->addDepartment($data);
        }
        return $this->success('添加成功');

    }


    /**
     * 删除节点
     */
    public function del(RequestInterface $request)
    {
        $param = $request->all();
        if (empty($param['id'])) {
            return $this->error();
        }
        $id = $param['id'];
        try {

            $info = Department::query()->where(['parent_id' => $id,'type'=>2])->first();
//            echo $info->toSql();
//            var_dump($info->toArray());
            if ($info) {
                throw new \Exception('删除失败！请先删除子节点');
            } else {
                $info = Department::find($id);

                if($info->type==3){
                    throw new \Exception('不可操作人员');

                }
                if (!empty($info)) {
                    $return = $info->delete();
                    if ($return) {
                        // 删除关联的角色
//                        AuthRoleRule::query()->where(['rule_id' => $id])->delete();
                    } else {
                        throw new \Exception('删除失败');
                    }
                } else {
                    throw new \Exception('已被删除,不可重复操作');
                }


            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), []);
        }
//        }


        return $this->success('删除成功', []);
    }

    function list()
    {
        $all_menu = $this->menuService->allStaff();
        return $this->success('返回成功', $all_menu);
    }
    function departmentList(){
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;
       $list =  Department::where(['company_id'=>$company_id,'status'=>0,'parent_id'=>0])->get()->toArray();
       return $this->success('返回成功',$list);






    }

    function addDepartment()
    {
        $param = $this->request->all();
        $company_id = $this->request->switch_company;
        $company_name = UserCompany::where('id', $company_id)->value('company_name');
        if (empty($param['department_name'])) {
            return $this->error('参数缺失');
        }
        $data['company_id'] = $company_id;
        $data['company_name'] = $company_name;
        $data['department_name'] = $param['department_name'];
        $data['add_time'] = date("Y-m-d H:i:s");
        Department::insert($data);
        return $this->success('添加成功');
    }

    function editDeparDepartmenttment()
    {
        $param = $this->request->all();
        $company_id = $this->request->switch_company;
        $company_name = UserCompany::where('id', $company_id)->value('company_name');
        if (empty($param['department_name']) || empty($param['department_id'])) {
            return $this->error('参数缺失');
        }
        $data['company_id'] = $company_id;
        $data['company_name'] = $company_name;
        $data['department_name'] = $param['department_name'];
        $data['add_time'] = date("Y-m-d H:i:s");
        Department::where('id', $param['department_id'])->update($data);
        return $this->success('编辑成功');
    }


    function info()
    {
        $param = $this->request->all();

        if (empty($param['department_id'])) {
            return $this->error('参数缺失');
        }
        $info = Department::where('id', $param['department_id'])->first();
        return $this->success('返回成功', $info);
    }


    function listold()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $company_id = $this->request->switch_company;

        $where = [];
        $where['company_id'] = $company_id;
        $size = empty($param['size']) ? 10 : $param['size'];
        $where['status'] = 0;
        $list = Department::where($where)->paginate($size, ['*'], 'current');
        return $this->success('返回成功', $list);


    }

    function delold()
    {
        $param = $this->request->all();
        if (empty($param['department_id'])) {
            return $this->error('参数缺失');
        }

        Department::where('id', $param['department_id'])->update(['status' => 1]);
        return $this->success('删除成功');
    }


}