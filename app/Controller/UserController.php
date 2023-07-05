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
use App\Model\Department;
use App\Model\Position;
use App\Model\User;
use App\Model\UserCompany;
use App\Model\UserInfo;
use App\Model\UserLoginLog;
use App\Model\UserOrderGroup;
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
 * @AutoController(prefix="/api/user")
 * @Middlewares({
 *     @Middleware(CheckToken::class)
 * })
 */
class UserController extends BaseController
{

    use QrCodeProduce;

    /**
     * @param RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var LoginService
     */
    private $loginService;


    function info(RequestInterface $request)
    {
//        $res = $this->request->getServerParams();
//        var_dump($res);
        $user_info = $request->user_info;
        $company_id = $request->switch_company;
        $user_info['current_company_id'] = $company_id;
        echo '*****)(______';
        echo $company_id . '&' . $user_info['id'];
        echo CompanyUserRelate::where(['company_id' => $company_id, 'user_id' => $user_info['id']])->toSql();
        $user_relate = CompanyUserRelate::where(['company_id' => $company_id, 'user_id' => $user_info['id']])->first();
        var_dump($user_relate);
//        $user_info['user_type'] = $user_relate->type;
        if (empty($company_id)) {
            $user_relate->isAdministrator = 1;
        }
        if(empty($user_info['userName'])){
//            $user_info['userName'] = $user_info['nickName'];
            $user_info['userName'] = '云衣客官';
        }
        $user_info['isAdministrator'] = $user_relate->isAdministrator;
        return $this->success('返回成功', $user_info);
    }

    function finishInfo(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;
        $use_id = $user_info['id'];
        $data = [];
        if (!empty($param['real_name'])) {
            $data['real_name'] = $param['real_name'];
        }
        if (!empty($param['email'])) {
            $data['email'] = $param['email'];
        }
        if (!empty($param['address'])) {
            $data['address'] = $param['address'];
        }
        if (!empty($param['avatar'])) {
            $data['avatar'] = $param['avatar'];
        }
        if (!empty($data)) {
            User::query()->where('id', $use_id)->update($data);
        }
        return $this->success();


    }

    function uploadFile(RequestInterface $request, ResponseInterface $response)
    {
//      $info =   \config('server.settings');
//      var_dump($info);
//       echo  $request->file('file')->getClientFilename().'/n';
//        echo  $request->file('file')->getBasename().'/n';
//        echo  $request->file('file')->getFilename().'/n';
//        echo  $request->file('file')->getPathname();

        $qiniu = qiniu::getInstance();

//        $qiniu->upload($request);
        try {
            $result = $qiniu->upload($request, $response);
//            $result = LoginService::upload($request);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
//        return $this->success('上传成功',$result);
        return $result;
    }

    /**
     * 团队用户列表
     */

    function userList(RequestInterface $request)
    {
        $param = $request->all();
        $user_info = $request->user_info;
        $where = [];
        $size = empty($param['size']) ? 10 : $param['size'];

//        if (isset($param['status'])) {
        $where['status'] = 1;
//        }
        if (isset($param['username'])) {
            $where['user_name'] = $param['username'];

        }
        $get_company_id = empty($param['company_id']) ? 0 : $param['company_id'];
        $switch_company_id = $request->switch_company;
        if ($user_info['is_admin'] && empty($switch_company_id)) {
            $where['company_id'] = $get_company_id;
            $list = CompanyUserRelate::with('user')->where($where)->paginate($size, ['*'], 'current');

        } else {
//            $switch_company_id = $request->switch_company;
            if (empty($switch_company_id)) {
                return $this->error('未切换到团队');
            } else {
                $where['company_id'] = $switch_company_id;
                $list = CompanyUserRelate::with('user')->where($where)->paginate($size, ['*'], 'current');
            }

        }

        return $this->success('返回成功', $list);


    }


    /**
     *我的邀请列表
     */

    function inviteList(RequestInterface $request)
    {
        $param = $request->all();
        $where = [];
        $size = empty($param['size']) ? 10 : $param['size'];

        if (isset($param['status'])) {
            $where['status'] = $param['status'];
        }
        if (isset($param['username'])) {
//            $where['userName'] = $param['username'];
//            $where['user_name'] =['like', $param['username'].'%'];
            $where['user_name'] = $param['username'];

        }
        $user_info = $request->user_info;
//        $redis = RedisUtil::getInstance();
//        $switch_company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $switch_company_id = $this->request->switch_company;
        $list = [];
        $where['form_user_id'] = $user_info['id'];
        if (empty($switch_company_id)) {
            return $this->error('未切换到团队');
        } else {
            $where['company_id'] = $switch_company_id;
            $list = CompanyUserRelate::with('user')->where($where)->paginate($size, ["*"], 'current');
//            var_dump($list);
//            CompanyUserRelate::query()->where(['company_id'=>$switch_company_id,'status'=>1,])
        }
        if (empty($list)) {
            return $this->error('未加入团队');
        } else {
            return $this->success('返回成功', $list);
        }
    }

    /**
     *申请加入列表
     */

    function applyList(RequestInterface $request)
    {
        $param = $request->all();
        $where = [];
        $size = empty($param['size']) ? 10 : $param['size'];
        if (isset($param['status'])) {
            $where['status'] = $param['status'];
        }
        if (isset($param['username'])) {
//            $where['userName'] = $param['username'];
//            $where['user_name'] =['like', $param['username'].'%'];
            $where['user_name'] = $param['username'];

        }
        $user_info = $request->user_info;
        $redis = RedisUtil::getInstance();
        $switch_company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $list = [];
//        $where['form_user_id'] = $user_info['id'];
        $where['form_user_id'] = 0;

        if (empty($switch_company_id)) {
            return $this->error('未切换到团队');
        } else {
            $is_admin = CompanyUserRelate::query()->where(['user_id' => $user_info['id'], 'isAdministrator' => 1, 'company_id' => $switch_company_id])->first();
            if (empty($is_admin)) {
                return $this->error('没有该公司管理员权限');
            }
            $where['company_id'] = $switch_company_id;
            $list = CompanyUserRelate::with('user')->where($where)->paginate($size, ["*"], 'current');
//            var_dump($list);
//            CompanyUserRelate::query()->where(['company_id'=>$switch_company_id,'status'=>1,])
        }
        if (empty($list)) {
            return $this->error('未加入团队');
        } else {
            return $this->success('返回成功', $list);
        }
    }


    function agreenApply(RequestInterface $request)
    {
        $param = $request->all();
        $where = [];

        if (!isset($param['status']) || empty($param['relate_id'])) {

            return $this->error('参数缺失');
        }

        $user_info = $request->user_info;
        $redis = RedisUtil::getInstance();
        $switch_company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $list = [];
        if (empty($switch_company_id)) {
            return $this->error('未切换到团队');
        } else {
            $is_admin = CompanyUserRelate::query()->where(['user_id' => $user_info['id'], 'isAdministrator' => 1, 'company_id' => $switch_company_id])->first();
            if (empty($is_admin)) {
                return $this->error('没有该公司管理员权限');
            }
            $apply_info = CompanyUserRelate::where(['id' => $param['relate_id'], 'company_id' => $switch_company_id])->first();
            if (empty($apply_info)) {
                return $this->error('不可跨公司操作');
            }
            CompanyUserRelate::query()->where('id', $param['relate_id'])->update(['status' => $param['status']]);
//            var_dump($list);
//            CompanyUserRelate::query()->where(['company_id'=>$switch_company_id,'status'=>1,])
        }
        return $this->success('操作成功');
    }

    function personInfo(RequestInterface $request)
    {
        $user_info = $request->user_info;
        $company_id = $request->switch_company;
//        var_dump($user_info);
        $data['user_id'] = $user_info['id'];
        $user_info = User::query()->where('id', $user_info['id'])->first()->toArray();
        $user_base_info = UserInfo::query()->where('user_id', $user_info['id'])->orderBy('last_login_date', 'desc')->first();
        $data['username'] = $user_info['userName'];
        $data['avatar'] = $user_info['avatar'];
        $data['phone'] = $user_info['phone'];
        $data['real_name'] = $user_info['real_name'];
        $data['email'] = $user_info['email'];
        $data['current_company_id'] = $request->switch_company;
        $data['last_login_date'] = $user_base_info->last_login_date;
        $data['last_login_ip'] = $user_base_info->last_login_ip;
        $data['is_admin'] = $user_info['is_admin'];
//        $user_type = CompanyUserRelate::query()->where(['user_id' => $user_info['id'], 'status' => 1, 'company_id' => $company_id, 'switch' => 1])->value('type');
//        $data['user_type'] = $user_type;
        $user_type = CompanyUserRelate::query()->where(['user_id' => $user_info['id'], 'status' => 1, 'company_id' => $company_id, 'switch' => 1])->first();
        $data['position_id'] = $user_type->position_id;
        $data['department_id'] = $user_type->department_id;
        $data['position_name'] = $user_type->position_name;
        $data['department_name'] = $user_type->department_name;
        $data['position_name'] = UserOrderGroup::where('user_id',$user_info['id'])->value("position_name");

        $company_list = CompanyUserRelate::query()->select('company_id', 'company_name', 'switch')->where(['user_id' => $user_info['id'], 'status' => 1])->get();
        if (empty($company_list)) {
            $data['companyList'] = [];
        } else {
            $data['companyList'] = $company_list->toArray();
        }
        return $this->success('返回成功', $data);

    }


    function finishApply(RequestInterface $request)
    {
        $user_info = $request->user_info;
        $param = $request->all();
//        var_dump($param);
        if (empty($param['type']) || !isset($param['is_profession']) || !isset($param['relate_id'])) {
            return $this->error();
        }
//        $admin = CompanyUserRelate::where(['isAdministrator'=>1,'status'=>1])->first();
//        if(empty($admin)){
//            return  $this->error('操作失败');
//        }
//        $son = CompanyUserRelate::where(['id'=>$param['relate_id'],'status'=>0])->first();
//        if(empty($son)){
//            return  $this->error('操作失败');
//
//        }
        $redis = RedisUtil::getInstance();
        $relate_info = CompanyUserRelate::where(['id' => $param['relate_id']])->first();
        $ruser_id = $relate_info->user_id;
        $company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $company_info = UserCompany::where('id', $company_id)->first();
        if (empty($company_info)) {
            return $this->error();
        }
        $company_info = $company_info->toArray();
        $user_company['company_name'] = $company_info['company_name'];
        $user_company['isAdministrator'] = $ruser_id == $user_info['id'] ? 1 : 0;
        $user_company['type'] = $param['type'];
        $user_company['is_profession'] = $param['is_profession'];
        $user_company['status'] = 1;
        CompanyUserRelate::query()->where('id', $param['relate_id'])->update($user_company);
        return $this->success();
    }

    function acceptInvite(RequestInterface $request)
    {
        $data = $request->all();

//        try {
//            $arr = LoginService::upload($request);
//        }catch (\Exception $e){
//            return $response->json(['code' => 1, $e->getMessage(), []]);
//        }

        try {
//            if (!isset($data['phone']) || !isset($data['username']) || !isset($data['password']) || !isset($data['repassword'])) {
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['repassword']) || empty($data['company_id']) || empty($data['user_id'])) {

                throw new Exception("参数缺失");
            } else {
                if (!empty($data['phone']) && !empty($data['very_code'])) {
                    $request_code = $this->redis->getKeys($data['phone'] . '_code');
                    if ($request_code != $data['very_code']) {
//                        throw new Exception( '验证码不匹配');
                    }
                }
                unset($data['very_code']);
                $where['userName'] = $data["username"];

                $info = User::query()->where($where)->first();

                if ($info) {
                    throw new Exception("用户名已被注册");
                } else {
                    $password = UserAuthService::generateFormattedPassword($data['password']);
//                    $ip = UserAuthService::getip();
                    $ip = '127.0.0.1';
                    $add = array("userName" => $data['username'], "creation_time" => date("Y-m-d H:i:s"), "pwd" => $password, "client_ip" => $ip, "phone" => $data['phone']);

                    $id = User::query()->insertGetId($add);
                    $relate_data['company_id'] = $data['company_id'];
                    $relate_data['user_id'] = $id;
                    $relate_data['status'] = 0;
                    $relate_data['creation_time'] = date("Y-m-d H:i:s");
                    $relate_data['form_user_id'] = $data['user_id'];
                    $relate_data['invite_code'] = $this->getCode($data['company_id'], $id);
                    $relate_data['user_name'] = $data['username'];
                    $relate_id = CompanyUserRelate::insertGetId($relate_data);
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


    function loginLog(RequestInterface $request)
    {
        $user_id = $request->user_info['id'];
        $param = $request->all();
        $size = empty($param['size']) ? 10 : $param['size'];
        $logs = UserLoginLog::where('user_id', $user_id)->select('client_ip as ip', 'where as system', 'system as browser', 'user_name', 'create_time')->orderByDesc('id')->paginate($size, ["*"], 'current');
        return $this->success('请求成功', $logs);
    }


    function myCode(RequestInterface $request)
    {
        $user_info = $request->user_info;
        $redis = RedisUtil::getInstance();

        $company_id = $redis->getKeys('switch_company_' . $user_info['id']);
        $company_id = empty($company_id) ? 0 : $company_id;
//        echo $user_info['id'];
        $url = CompanyUserRelate::where(['user_id' => $user_info['id'], 'company_id' => $company_id])->value('invite_code');
        return $this->success('返回成功', $url);


    }


    function addAdmin()
    {
        $data = $this->request->all();


        try {
            if (!isset($data['username']) || !isset($data['password']) || empty($data['role_id'])) {

                throw new \Exception("参数缺失");
            } else {

                $where['userName'] = $data["username"];

                $info = User::query()->where($where)->first();
                $info_p = User::query()->where(['phone' => $data['phone']])->first();

                if ($info || $info_p) {
                    throw new \Exception("用户名/手机号已被注册");
                } else {
                    $password = UserAuthService::generateFormattedPassword($data['password']);
//                    $ip = UserAuthService::getip();
                    $ip = '127.0.0.1';
                    $add = array("is_admin" => 1, "userName" => $data['username'], "creation_time" => date("Y-m-d H:i:s"), "pwd" => $password, "client_ip" => $ip, "phone" => $data['phone']);

                    $id = User::query()->insertGetId($add);
                    if (!$id) {
                        throw new \Exception('注册失败');
                    }
                }
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        $user_company['company_id'] = 0;
        $user_company['user_id'] = $id;
//        $user_company['invite_code'] = $this->getCode($param['company_id'],$user_info['id']);

        $user_company['company_name'] = '云衣公设';
        $user_company['creation_time'] = date("Y-m-d H:i:s");
        $user_company['isAdministrator'] = 0;
//        $user_company['type'] = 1;
//        $user_company['is_profession'] = 1;
        $user_company['user_name'] = $data["username"];
        $user_company['switch'] = 1;
        $user_company['status'] = 1;
        $role_id = $data['role_id'];
        $role_info = AuthRole::where('id', $role_id)->select('id', 'name')->first();
        $user_company['department_id'] = $role_info['id'];
        $user_company['department_name'] = $role_info['name'];
        $relate_id = CompanyUserRelate::query()->insertGetId($user_company);

        AuthRoleRelate::insertGetId(['role_id' => $role_id, 'relate_id' => $relate_id]);
        return $this->success('添加成功');
    }

    function adminInfo()
    {


        $user_info = $this->request->user_info;
        $param = $this->request->all();
        if (empty($param['user_id'])) {
            return $this->error('参数缺失');
        }
        $user_info = User::where('user.id', $param['user_id'])->join('company_user_relate', 'user.id', '=', 'company_user_relate.user_id')->select('company_user_relate.user_id', 'company_user_relate.user_name', 'company_user_relate.department_id as role_id', 'user.pwd as password', 'user.phone', 'company_user_relate.id as relate_id')->first();
        return $this->success('返回成功', $user_info);
    }


    function editAdmin()
    {
        $user_info = $this->request->user_info;
        $data = $this->request->all();


        try {
            if (!isset($data['username']) || empty($data['role_id']) || empty($data['user_id'])) {

                throw new \Exception("参数缺失");
            } else {

                $where['id'] = $data["user_id"];

                $info = User::query()->where($where)->first();

                if (!$info) {
                    throw new \Exception("信息不存在");
                } else {

//                    $ip = UserAuthService::getip();
                    $ip = '127.0.0.1';
                    $add = array("is_admin" => 1, "userName" => $data['username'], "modiry_time" => date("Y-m-d H:i:s"), "client_ip" => $ip, "phone" => $data['phone']);
                    if (isset($data['password'])) {
                        $password = UserAuthService::generateFormattedPassword($data['password']);
                        $add['pwd'] = $password;
                    }
                    $id = User::query()->where('id', $data['user_id'])->update($add);
                    if (!$id) {
                        throw new \Exception('更新失败');
                    }
                }
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        $user_company['company_id'] = 0;
        $user_company['user_id'] = $data['user_id'];
//        $user_company['invite_code'] = $this->getCode($param['company_id'],$user_info['id']);

        $user_company['company_name'] = '云衣公设';
        $user_company['creation_time'] = date("Y-m-d H:i:s");
        $user_company['isAdministrator'] = 0;
        $user_company['type'] = 1;
//        $user_company['is_profession'] = 1;
        $user_company['user_name'] = $data["username"];
        $user_company['switch'] = 1;
        $user_company['status'] = 1;
        $role_id = $data['role_id'];
        $role_info = AuthRole::where('id', $role_id)->select('id', 'name')->first();
        $user_company['department_id'] = $role_info['id'];
        $user_company['department_name'] = $role_info['name'];
        $relate_id = CompanyUserRelate::where(['user_id' => $data['user_id'], 'status' => 1])->value('id');
        $relate_id = CompanyUserRelate::query()->where(['id' => $relate_id])->update($user_company);

        AuthRoleRelate::where(['relate_id' => $relate_id])->update(['role_id' => $role_id, 'relate_id' => $relate_id]);
        return $this->success('编辑成功');
    }


    function delAdmin()
    {
        $param = $this->request->all();
        if (empty($param['user_id'])) {
            return $this->error('参数缺失');
        }
        User::where(['id' => $param['user_id']])->update(['status' => 0]);
        return $this->success('删除成功');
    }


    function adminList()
    {
        $param = $this->request->all();
        $size = empty($param['size']) ? 10 : $param['size'];
        $where = [];
        $where['is_admin'] = 1;
        $where['user.status'] = 1;
        $where['company_user_relate.status'] = 1;
        if (!empty($param['username'])) {
            $where['userName'] = $param['username'];
        }

        $list = User::where($where)->join('company_user_relate', 'user.id', '=', 'company_user_relate.user_id')->select('user.id as user_id', 'user.userName', 'user.is_admin', 'user.avatar', 'company_user_relate.department_name', 'user.creation_time as add_time')->orderByDesc('user.id')->paginate($size, ["*"], 'current');
        return $this->success('返回成功', $list);


    }

    //部门1
    function roleList()
    {
        $param = $this->request->all();
        $where['status'] = 1;
        $where['is_department'] = empty($param['is_department']) ? 0 : 1;
        $company_id = $this->request->switch_company;
        $where['company_id'] = $company_id;
        $list = AuthRole::where($where)->get()->toArray();
        return $this->success('返回成功', $list);

    }

//下拉部门
    function getDepartment()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $where = [];
        $company_id = $this->request->switch_company;
        $where['company_id'] = $company_id;
        $where['status'] = 0;
        $where['parent_id'] = 0;
        $list = Department::where($where)->select('id as department_id', 'name as department_name')->get()->toArray();
        $list1 = Department::where(['parent_id' => 0, 'yu_type' => 1])->select('id as department_id', 'name as department_name')->get()->toArray();
//        var_dump($list1);
//        var_dump($list1);
        $list = array_merge($list, $list1);
        if(empty($list)){
            $list[] = Department::where('id',1)->select('id as department_id', 'name as department_name')->first()->toArray();
        }
        return $this->success('返回成功', $list);
    }


    function getPosition()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $where = [];
        if (empty($param['department_id'])) {
            return $this->error('参数缺失');
        }
        $where['parent_id'] = $param['department_id'];
        $where['type'] = 2;
        $position_list = Department::where($where)->select('id as position_id', 'name as position_name')->get()->toArray();
        return $this->success('获取成功', $position_list);
    }

    //加入申请 团队管理员完善用户信息
    function finishApplyInfo()
    {

        $user_info = $this->request->user_info;
        $param = $this->request->all();
        var_dump($param);
        $company_id = $this->request->switch_company;
        if (!isset($param['is_profession']) || !isset($param['relate_id']) || empty($param['department_id']) || empty($param['role_arr']) || empty($param['position_id'])) {
            return $this->error('参数缺失');
        }
        //传角色数组过来

        /**
         * 传角色数组过来
         */
//        $param['role_arr'] = [1,2,3];
        try {

            Db::beginTransaction();
            $relate_info = CompanyUserRelate::where(['id' => $param['relate_id']])->first();
            $ruser_id = $relate_info->user_id;
            $relate_info0 = CompanyUserRelate::where(['user_id' => $ruser_id, 'company_id' => 0])->first();
            $relate_info1 = CompanyUserRelate::where(['user_id' => $ruser_id, 'company_id' => $company_id])->first();
//            var_dump($relate_info1->toArray());
//            echo $company_id;
            if (!empty($relate_info1->form_user_id) && !empty($param['username'])) {
                $password = UserAuthService::generateFormattedPassword($param['password']);

                $ip = '127.0.0.1';
                $add = array("userName" => $param['username'], "creation_time" => date("Y-m-d H:i:s"), "pwd" => $password, "client_ip" => $ip, "phone" => $param['phone']);
                $is_has = User::query()->where('phone', $param['phone'])->first();
                if (!empty($is_has)) {
                    throw new Exception('手机号已被使用');
                }

                User::query()->where('id', $ruser_id)->update($add);
                $user_company0['user_name'] = $param["username"];
                $user_company0['switch'] = 1;
                $user_company0['status'] = 1;
                $user_company0['department_id'] = 0;
                $user_company0['position_id'] = 0;

                $user_company0['department_name'] = '个人';
                $user_company0['position_name'] = '设计师';
                CompanyUserRelate::query()->where('id', $relate_info0->id)->update($user_company0);
                $user_company['user_name'] = $param["username"];

            }
//            $role_info = AuthRole::where('id', $param['department_id'])->first()->toArray();
            $role_info_arr = AuthRole::whereIn('id', $param['role_arr'])->first()->toArray();

            $company_info = UserCompany::where('id', $company_id)->first();
            if (empty($company_info)) {
//                return $this->error();
                throw new \Exception('公司信息不存在');
            }
            $company_info = $company_info->toArray();
            $user_company['invite_code'] = $this->getCode($company_id, $user_info['id']);

            $user_company['company_name'] = $company_info['company_name'];
            $user_company['isAdministrator'] = $ruser_id == $user_info['id'] ? 1 : 0;
//            $user_company['type'] = $param['type'];
            $user_company['is_profession'] = $param['is_profession'];
            $user_company['status'] = 1;
            $user_company['department_id'] = $param['department_id'];
//            $user_company['department_name'] = $role_info['name'];
            $user_company['department_name'] = Department::where('id', $param['department_id'])->value('name');
            $user_company['position_id'] = $param['position_id'];
            $user_company['position_name'] = Department::where('id', $param['position_id'])->value('name');
            var_dump($user_company);
            CompanyUserRelate::query()->where('id', $param['relate_id'])->update($user_company);
            $role_relate = AuthRoleRelate::where(['relate_id' => $param['relate_id']])->first();
            if (!empty($role_relate)) {
//                $role_relate_data['role_id'] = $param['department_id'];
//                AuthRoleRelate::where(['relate_id' => $param['relate_id']])->update($role_relate_data);
                AuthRoleRelate::where('relate_id', $param['relate_id'])->delete();
            }
//            else {
//                AuthRoleRelate::insertGetId(['relate_id' => $param['relate_id'], 'role_id' => $param['department_id']]);
//
//            }
            foreach ($param['role_arr'] as $value) {
                AuthRoleRelate::insertGetId(['relate_id' => $param['relate_id'], 'role_id' => $value]);
            }
            $zz_data['name'] = empty($param['username']) ? $relate_info->user_name : $param['username'];
            $zz_data['company_id'] = $company_id;
            $zz_data['company_name'] = $company_info['company_name'];
            $zz_data['parent_id'] = $param['position_id'];
            $zz_data['type'] = 3;
            $zz_data['user_id'] = $ruser_id;
            $zz_data['status'] = 0;
            $zzinfo = Department::where(['company_id' => $company_id, 'user_id' => $ruser_id])->first();
            if (!empty($zzinfo)) {
                Department::where(['company_id' => $company_id, 'user_id' => $ruser_id])->update($zz_data);
            } else {
                $zz_data['add_time'] = date("Y-m-d H:i:s");
                Department::insert($zz_data);
            }
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->error($e->getMessage());
        }
        Db::commit();
        return $this->success();
    }

    function bindMobile()
    {
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        $redis = RedisUtil::getInstance();
        try {
            if (!isset($param['phone']) || !isset($param['code'])) {

                throw new Exception("参数缺失");
            } else {
                if (!empty($param['phone']) && !empty($pram['code'])) {
                    $request_code = $redis->getKeys($param['phone'] . '_code');
                    if ($request_code != $param['code']) {
//                        throw new Exception( '验证码不匹配');
                    }
                }
            }
            $is_bind = User::where('phone', $param['phone'])->first();
            if (!empty($is_bind)) {
                if ($is_bind->id == $user_info['id']) {
                    return $this->error('不可重复绑定');
                } else {
                    unset($user_info['id']);
                    $info = array_merge($is_bind, $user_info);
                    User::where('id', $is_bind->id)->update($info);
                }
            } else {
                User::where('id', $user_info['id'])->update(['phone' => $param['phone']]);
            }


        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('绑定成功');
    }

    function getIp()
    {
//        $redis = RedisUtil::getInstance();
//        $redis->setKeys('22','ddd');
//        $r = $redis->getKeys('22');
//        $this->success($r);
//        $info = $this->request->getServerParams();
        $ip = $this->ip();
//        var_dump($info);
//       $ip =  UserAuthService::get_client_ip();
//       var_dump($ip);
        return $this->success('sss', $ip);
    }

    public function ip()
    {
        $res = $this->request->getServerParams();
        return $this->success('fanhui', $res);
//        if(isset($res['http_client_ip'])){
//            return $res['http_client_ip'];
//        }elseif(isset($res['http_x_real_ip'])){
//            return $res['http_x_real_ip'];
//        }elseif(isset($res['http_x_forwarded_for'])){
//            //部分CDN会获取多层代理IP，所以转成数组取第一个值
//            $arr = explode(',',$res['http_x_forwarded_for']);
//            return $arr[0];
//        }else{
//            return $res['remote_addr'];
//        }

    }

    function changePwd(){
        $param = $this->request->all();
        $user_info = $this->request->user_info;
        if(empty($param['password']) || empty($param['re_password'])){
            return $this->error();
        }
        if($param['password'] != $param['re_password']){
            return $this->error('两次密码输入不一致');

        }
        $password = UserAuthService::generateFormattedPassword($param['password']);

        User::where('id',$user_info['id'])->update(['pwd'=>$password]);

        return $this->success();

    }


}