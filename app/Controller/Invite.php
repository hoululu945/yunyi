<?php


namespace App\Controller;

use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Common\Controller\BaseController;
use App\Common\Tool\qiniu;
use App\Common\Tool\QrCodeProduce;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRole;
use App\Model\AuthRoleRelate;
use App\Model\CompanyUserRelate;
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
 * @AutoController(prefix="/api/invite")
 *
 */
class Invite extends BaseController
{
    use QrCodeProduce;

    function acceptInvite(RequestInterface $request)
    {
        $data = $request->all();

//        try {
//            $arr = LoginService::upload($request);
//        }catch (\Exception $e){
//            return $response->json(['code' => 1, $e->getMessage(), []]);
//        }
        $redis = RedisUtil::getInstance();
        try {
//            if (!isset($data['phone']) || !isset($data['username']) || !isset($data['password']) || !isset($data['repassword'])) {
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['repassword']) || empty($data['company_id']) || empty($data['user_id'])) {
//                throw new Exception()
                throw new Exception("参数缺失");
            } else {
                if (!empty($data['phone']) && !empty($data['very_code'])) {
                    $request_code = $redis->getKeys($data['phone'] . '_code');
                    if ($request_code != $data['very_code']) {
//                        throw new Exception( '验证码不匹配');
                    }
                }
                unset($data['very_code']);
                $where['userName'] = $data["username"];

                $info = User::query()->where($where)->first();
                $info_p = User::query()->where(['phone'=>$data['phone']])->first();

                if ($info || $info_p) {
                    throw new Exception("用户名/手机号已被注册");
                } else {
                    $password = UserAuthService::generateFormattedPassword($data['password']);
//                    $ip = UserAuthService::getip();
                    $ip = '127.0.0.1';
                    $add = array("userName" => $data['username'], "creation_time" => date("Y-m-d H:i:s"), "pwd" => $password, "client_ip" => $ip, "phone" => $data['phone']);

                    $id = User::query()->insertGetId($add);
//                    $relate_data['company_id'] = $data['company_id'];
//                    $relate_data['user_id'] = $id;
//                    $relate_data['status'] = 0;
//                    $relate_data['creation_time'] = date("Y-m-d H:i:s");
//                    $relate_data['form_user_id'] = $data['user_id'];
//                    $relate_data['invite_code'] = $this->getCode($data['company_id'],$id);
//                    $relate_data['user_name'] = $data['username'];
//                    $relate_id = CompanyUserRelate::insertGetId($relate_data);
//                    $role_id = config('limit_design_role');
//                    AuthRoleRelate::insertGetId(['role_id' => $role_id, 'relate_id' => $relate_id]);


                    $user_company0['company_id'] = 0;
                    $user_company0['user_id'] = $id;
//        $user_company['invite_code'] = $this->getCode($param['company_id'],$user_info['id']);

                    $user_company0['company_name'] = '云衣公设';
                    $user_company0['creation_time'] = date("Y-m-d H:i:s");
                    $user_company0['isAdministrator'] = 0;
                    $user_company0['type'] = 1;
//        $user_company['is_profession'] = 1;
                    $user_company0['user_name'] = $data["username"];
                    $user_company0['switch'] = 1;
                    $user_company0['status'] = 1;

                    $rid = CompanyUserRelate::query()->insertGetId($user_company0);
                    $role_info = AuthRole::where(['type' => 1, 'position_type' => 1])->first();
                    if (empty($role)) {
                        throw new Exception('未设置预设角色');

//                return $response->json(['code' => -1, 'message' => '未设置预设角色', 'data' => []]);
                    }
//                    $role_id = config('limit_design_role');
                    AuthRoleRelate::insertGetId(['role_id' => $role_info->id, 'relate_id' => $rid]);

                    $user_company['company_id'] = $data['company_id'];
                    $user_company['user_id'] = $id;
                    $user_company['form_user_id'] = $data['user_id'];
                    $user_company['company_name'] = '';
                    $user_company['status'] = 0;
                    $user_company['creation_time'] = date("Y-m-d H:i:s");
                    $user_company['isAdministrator'] = 0;
                    $user_company['type'] = 1;
                    $user_company['user_name'] = $data["username"];

                    $relate_id = CompanyUserRelate::query()->insertGetId($user_company);
//                    $role_id = config('company_design_role');
//                    AuthRoleRelate::insertGetId(['role_id' => $role_id, 'relate_id' => $relate_id]);
                    if (!$id) {
                        throw new Exception('接受失败');
                    }
                }
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), []);
        }

        return $this->success('接受成功', []);
    }


}