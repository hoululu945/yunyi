<?php
declare(strict_types=1);


namespace App\Controller;


use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Common\Controller\BaseController;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use App\Model\CompanyUserRelate;
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
 * @AutoController(prefix="/api/menu")
 * @Middlewares({
 *  @Middleware(CheckToken::class)
 * })
 */

///**
// * @AutoController()
// */

class MenuController extends BaseController
{

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var MenuService
     */
    private $menuService;

    const BaseRole = 1;

    function authRule(RequestInterface $request, ResponseInterface $response)
    {
//        $user_info = $request->user_info;
//        $is_admin = $user_info['is_admin'];
        $all_menu = $this->menuService->allRule();
        return $this->success('返回成功', $all_menu);
//       return $response->json($all_menu);


    }


    function roleRule(RequestInterface $request, ResponseInterface $response)
    {

        $rules = $this->menuService->allRoleRule($role_id = 1);
        return $this->success('返回成功', $rules);
//        return $response->json($rules);


    }

    function editRoleRule(RequestInterface $request, ResponseInterface $response)
    {

        $result = $this->menuService->editRoleRule($data = []);
        return $this->success('返回成功', $result);

//        return $response->json($result);


    }

//parent_id: 5
//title: test
//name: ad
//param: in
//icon:
//list_order:
    function addMenu(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->all();

        foreach ($data as $key => $val) {
            if (is_null($val)) {
                unset($data[$key]);
            }
        }
        !isset($data['parent_id']) && $data['parent_id'] = 0;

        $data['list_order'] = intval($data['list_order']);
//        !isset($data['status']) && $data['status'] = 0;
        !isset($data['type']) && $data['type'] = 0;
        !isset($data['name']) && $data['name'] = '';
        !isset($data['title']) && $data['name'] = '';
        !isset($data['param']) && $data['param'] = '';
        !isset($data['is_open']) && $data['is_open'] = 1;

        if (isset($data['id']) && $data['id'] > 0) {
            $return = $this->menuService->editMenu($data['id'], $data);
        } else {
            $return = $return = $this->menuService->addMenu($data);
        }
//        return $response->json($return);
        return $this->success('返回成功', $return);


    }


    function getMainMenu(RequestInterface $request)
    {


        $user_id = $request->user_info['id'];
        $relate_switch_info = CompanyUserRelate::where(['user_id' => $user_id, 'switch' => 1])->first();
        if (!empty($relate_switch_info)) {
            $relate_id = $relate_switch_info->id;
            $relate_role_arr = AuthRoleRelate::query()->where('relate_id', $relate_id)->pluck('role_id')->toArray();

        }


        $rule_id_arr = AuthRoleRule::whereIn('role_id', $relate_role_arr)->pluck('rule_id')->toArray();
        if (!empty($rule_id_arr)) {
            $rule_id_arr = array_unique($rule_id_arr);
        }

        $rule_list = AuthRule::where(['type' => 1])->select('name as viewUrl', 'id')->get()->toArray();
        if (empty($rule_list)) {
            return $this->error();
        }
        foreach ($rule_list as $k => $v) {
            if (!empty($rule_id_arr)) {
//                if (in_array($v['id'], $rule_id_arr) && (!in_array($v['id'],[9,24]) || !empty($request->user_info['is_admin']))) {
                if (in_array($v['id'], $rule_id_arr) && !in_array($v['id'],[9,24])) {
                    if(in_array($v['id'],[17,31]) && empty($request->user_info['is_admin'])){
                        $rule_list[$k]['hasRight'] = false;
                        continue;
                    }

                    $rule_list[$k]['hasRight'] = true;

                } else {
                    $rule_list[$k]['hasRight'] = false;

                }
            } else {
                $rule_list[$k]['hasRight'] = true;

            }


        }
        return $this->success('返回成功', $rule_list);

//        var_dump($rule_id);


//        return $this->success('返回成功', $relate_id);

//        return $this->show(1,'请求成功',$name);
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
        $msg = '';
//        $ids = $param['ids'];
//        $ids = explode(',',$ids);
//        foreach ($ids as $id){
        try {
//            if(empty($param['id'])){
//                throw new Exception('缺失传递参数');
//            }
//            $id = $param['id'];
            $info = AuthRule::query()->where(['parent_id' => $id])->first();
            if ($info) {
                throw new Exception('删除失败！请先删除子权限');
            } else {
                $rule_info = AuthRule::find($id);
                if (!empty($rule_info)) {
                    $return = $rule_info->delete();
                    if ($return) {
                        // 删除关联的角色
                        AuthRoleRule::query()->where(['rule_id' => $id])->delete();
                    } else {
                        throw new Exception('删除失败');
                    }
                } else {
                    throw new Exception('已被删除,不可重复操作');
                }


            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), []);
        }
//        }


        return $this->success('删除成功', []);
    }


    function getRelateRule(RequestInterface $request)
    {
        $user_info = $request->user_info;
//        var_dump($user_info);
        $user_id = $user_info['id'];


        $redis = RedisUtil::getInstance();
        $company_switch_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $company_switch_id = empty($company_switch_id) ? 0 : $company_switch_id;

        $user_relate = CompanyUserRelate::query()->where(['user_id' => $user_id, 'company_id' => $company_switch_id])->first();
        if (empty($user_relate)) {
            $role_id = 1;
        } else {
            $relate_id = $user_relate->id;
            $relate_role = AuthRoleRelate::query()->where('relate_id', $relate_id)->first();
            if (empty($relate_role)) {
                $role_id = self::BaseRole;

            } else {
                $role_id = $relate_role->role_id;
            }

        }
//        echo $role_id."*******************";
//        AuthRoleRelate::query()->where([''])
        $rules = $this->menuService->allRoleRule($role_id);
        return $this->success('返回成功', $rules);


    }


}