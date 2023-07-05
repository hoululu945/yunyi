<?php


namespace App\Service;


use App\Common\Tool\RedisUtil;
use App\Model\AuthRole;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use App\Model\CompanyUserRelate;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Di\Annotation\Inject;

class RoleService
{

    const BaseRole = 1;
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var MenuService
     */
    private $menuService;


    /**
     *
     * @Inject
     * @var RequestInterface
     */
    private $request;

    function addRole($role_info, $rule_arr)
    {
        $role_info['create_time'] = date("Y-m-d H:i:s");
        $role_id = AuthRole::query()->insertGetId($role_info);
        $data['role_id'] = $role_id;
        $data['rule_ids'] = $rule_arr;

        if (!empty($rule_arr)) {
            $back = $this->menuService->editRoleRule($data);

        } else {
            $back = true;
        }
        return $back;
    }

    function editRole($role_info, $rule_arr)
    {
        $role_id = $role_info['role_id'];
        unset($role_info['role_id']);
        $role_info['update_time'] = date("Y-m-d H:i:s");

        AuthRole::query()->where('id', $role_id)->update($role_info);
        $data['role_id'] = $role_id;
        $data['rule_ids'] = $rule_arr;
//        $back = $this->menuService->editRoleRule($data);
        if (!empty($rule_arr)) {
            $back = $this->menuService->editRoleRule($data);

        } else {
            $back = true;
        }
        return $back;
    }


    function getRoleRuleName($user_id, $company_id = 0)
    {

        $user_relate = CompanyUserRelate::query()->where(['user_id' => $user_id, 'switch' => 1, 'status' => 1])->first();

        $company_switch_id = $user_relate->company_id;
        if (empty($user_relate)) {
            return false;
        } else {
            $relate_id = $user_relate->id;
            $relate_role = AuthRoleRelate::query()->where('relate_id', $relate_id)->get()->toArray();

            if (empty($relate_role)) {
                $role_id = 0;


            } else {
                $role_id = array_column($relate_role, 'role_id');
            }

        }

        if (empty($role_id)) {
            $rule_names = AuthRule::query()->pluck('name');
        } else {
            $rules = $this->menuService->allRoleRule($role_id);
            $rule_names = AuthRule::query()->whereIn('id', $rules)->pluck('name');
        }
        $redis = RedisUtil::getInstance();

        $rule_all = AuthRule::query()->pluck('name')->toArray();
        $redis->setKeys('rule_all', json_encode($rule_all));

        if (!empty($rule_names)) {
            $rule_names = array_unique($rule_names->toArray());
            $redis->hashSet('user_rule', strval($user_id), serialize($rule_names));
//            $redis->setKeys('rule_name_user'.$user_id,json_encode($rule_names));

        } else {
            $redis->hashSet('user_rule', $user_id, serialize([]));

//            $redis->setKeys('rule_name_user'.$user_id,json_encode([]));

        }
        return true;
//        $redis->setKeys('rule_name_user'.$user_id,json_encode($rule_names));
//        var_dump($rule_names);

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


}