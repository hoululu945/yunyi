<?php


namespace App\Controller;

use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Common\Tool\JwtTool;
use App\Common\Tool\LoginInfoTool;
use App\Common\Tool\QrCodeProduce;
use App\Common\Tool\RedisUtil;
use App\Controller\AbstractController;
use App\Model\AuthRole;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\CompanyUserRelate;
use App\Model\User;
use App\Model\UserLoginLog;
use App\Service\AliSmsUtilServer;
use App\Service\LoginService;
use App\Service\MenuService;
use App\Service\RoleService;
use App\Service\UserAuthService;
use Endroid\QrCode\QrCode;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use App\Middleware\Auth\CheckToken;


/**
 * Class LoginController
 * @package App\Controller
 * @AutoController(prefix="/api/login")
 */
class LoginController extends AbstractController
{
    use QrCodeProduce;

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var MenuService
     */
    private $menuService;


    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var LoginInfoTool
     */
    private $loginInfoTool;
    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var LoginService
     */
    private $loginService;


    /**
     * @Inject
     * @var RoleService
     */
    protected $roleService;


    protected $redis;

    function __construct()
    {
        parent::__construct();
        $this->redis = RedisUtil::getInstance();
    }

    use JwtTool;

//    /**
//     * @Middlewares({
//     *     @Middleware(CheckToken::class)
//     * })
//     */

    public function judge_agent()
    {

        $headers = $this->request->getHeaders();
        $ua = $headers['user-agent'][0] ;
        if (strpos($ua, 'MicroMessenger')) {
           return 1;
        }
        return 0;
    }
    public function login(RequestInterface $request, ResponseInterface $response)
    {

        $agent_type = $this->judge_agent();
        $sys = $this->loginInfoTool->getBrowser();
        $ip = $this->loginInfoTool->ip();
        $os = $this->loginInfoTool->os();
        $param = $request->all();

        $username = $param['username'];
        $password = $param['password'];
        try {
            if (strlen($username) == 0) {
                throw new Exception("登录账号不能为空！");
            }

            if (strlen($password) == 0) {
                throw new Exception("密码不能为空！");
            }
//            try {
                $user = UserAuthService::authorizeByPassword($username, $password, $ip);
//                $all_rule = $this->menuService->allRule();
//                var_dump($all_rule);
//                $this->redis->setKeys('all_auth', $all_rule);
                $token_arr = self::refreshGetToken($user);
//            } catch (\Exception $e) {
//                return $response->json(['code' => -1, 'message' => $e->getMessage(), 'date' => []]);
//            }

            UserLoginLog::insertGetId(['user_name' => $username, 'client_ip' => $ip, 'system' => $sys, 'user_id' => $user['id'], 'create_time' => date("Y-m-d H:i:s"), 'where' => $os]);
            $redis = RedisUtil::getInstance();
            if(empty($agent_type)){
                $token_user_key = strval($user['id']);
            }else{
                $token_user_key = "wechat-".$user['id'];

            }
            $redis->hashDel('access_token', $token_user_key);
            $r = $redis->hashSet('access_token',$token_user_key, $token_arr['access_token']);
            $company_id = CompanyUserRelate::where(['switch'=>1,'user_id'=>$user['id']])->value('company_id');
            var_dump($company_id);
            $rule_res = $this->roleService->getRoleRuleName($user['id'],$company_id);
//            var_dump($rule_res);
//            return $response->json(['code' => 200, 'message' => '登陆成功', 'data' => $token_arr]);
        } catch (\Exception $e) {
            return $response->json(['code' => -1, 'message' => $e->getMessage(), 'date' => []]);

        }
        return $response->json(['code' => 200, 'message' => '登陆成功', 'data' => $token_arr]);


    }

    public function register(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->all();


        try {
            Db::beginTransaction();
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['repassword'])) {

                throw new Exception("参数缺失");
            } else {
                if (!empty($data['phone']) && !empty($data['very_code'])) {
                    $request_code = $this->redis->getKeys($data['phone'] . '_code');
                    if ($request_code != $data['very_code']) {
                        throw new Exception( '验证码不匹配');
                    }
                }
                unset($data['very_code']);
                $where['userName'] = $data["username"];

                $info = User::query()->where($where)->first();
                $info_p = User::query()->where(['phone' => $data['phone']])->first();

                if ($info || $info_p) {
                    throw new Exception("用户名/手机号已被注册");
                } else {
                    $password = UserAuthService::generateFormattedPassword($data['password']);
//                    $ip = UserAuthService::getip();
                    $ip = '127.0.0.1';
                    $add = array("userName" => $data['username'], "creation_time" => date("Y-m-d H:i:s"), "pwd" => $password, "client_ip" => $ip, "phone" => $data['phone']);

                    $id = User::query()->insertGetId($add);
                    if (!$id) {
                        throw new Exception('注册失败');
                    }
                }
            }

            $user_company['company_id'] = 0;
            $user_company['user_id'] = $id;
//        $user_company['invite_code'] = $this->getCode($param['company_id'],$user_info['id']);

            $user_company['company_name'] = '云衣公设';
            $user_company['creation_time'] = date("Y-m-d H:i:s");
            $user_company['isAdministrator'] = 0;
//            $user_company['type'] = 1;
//        $user_company['is_profession'] = 1;
            $user_company['user_name'] = $data["username"];
            $user_company['switch'] = 1;
            $user_company['status'] = 1;
//        $role_id = config('limit_design_role');
            $role = AuthRole::where(['type' => 1, 'position_type' => 1])->first();
            if (empty($role)) {
                throw new Exception('未设置预设角色');

//                return $response->json(['code' => -1, 'message' => '未设置预设角色', 'data' => []]);
            }
//        $role_info = AuthRole::where('id',$role_id)->select('id','name')->first();
//        $user_company['department_id'] = $role_info['id'];
//        $user_company['department_name'] = $role_info['name'];
//            $user_company['department_id'] = 0;
//            $user_company['department_name'] = '';
            $user_company['department_id'] = 0;
            $user_company['position_id'] = 0;

            $user_company['department_name'] = '个人';
            $user_company['position_name'] = '设计师';
            $relate_id = CompanyUserRelate::query()->insertGetId($user_company);

            AuthRoleRelate::insertGetId(['role_id' => $role->id, 'relate_id' => $relate_id]);
        } catch (\Exception $e) {
            Db::rollBack();
            return $response->json(['code' => -1, 'message' => $e->getMessage(), 'data' => []]);
        }
        Db::commit();
        return $response->json(['code' => 200, 'message' => '注册成功', 'data' => []]);

    }

    /**
     * 获取验证码
     * type  1修改密码 需要发验证码
     */
    function getVerufyCode(RequestInterface $request, ResponseInterface $response)
    {
        $param = $request->all();
        $type = 0;
        try {


            if (empty($param['phone'])) {
                throw new Exception('手机号不能为空');
            }

            if (!empty($param['type'])) {
                $type = $param['type'];
            }
//        new Redis()
            $redis = RedisUtil::getInstance();
            $is_pub = $redis->getKeys($param['phone'] . '_code');
            if ($is_pub) {
                throw new Exception('验证码未过期，请稍后获取');

            }
            $user_info = User::query()->where('phone', $param['phone'])->first();
            if (!empty($user_info)) {
                if (!empty($user_info->userName) && empty($type)) {
                    throw new Exception('该手机号已注册不可重复注册');

                }

            }
            $reuslt = AliSmsUtilServer::sendSmsCode($param['phone']);
//        var_dump($reuslt);
            if (!empty($reuslt['Code']) && $reuslt['Code'] == 'OK') {
                return $response->json(['code' => 200, 'message' => '发送验证码成功', 'data' => []]);
            } else {
                throw new Exception('发送验证码失败');

            }
        } catch (\Exception $e) {
            return $response->json(['code' => -1, 'message' => $e->getMessage(), 'data' => []]);
        }
    }


    function acceptInvite(RequestInterface $request, ResponseInterface $response)
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
            return $response->json(['code' => -1, 'message' => $e->getMessage(), 'data' => []]);

        }

        return $response->json(['code' => 200, 'message' => '接受邀请成功', 'data' => []]);
    }


    function getWechatCode(RequestInterface $request, ResponseInterface $respons){

        $file_name = uniqid().'.png';
        $url_path = '/storage/image/';
        $file_path = config('root_path').$url_path;
        $param = $request->all();
        if(empty($param['fid'])){
//            $param['fid'] =11111;
            return  $respons->json(['code' => -1, 'message' => 'wsid缺失']);
        }
        $code_url =config('domain_url').'api/wechat/login?fdId='.$param['fid'];
        if(is_file($file_path.$file_name)) {
            unlink ($file_path.$file_name);
        }
        is_dir($file_path) OR mkdir($file_path, 0777, true);

        $set_log = true;
        $qrCode = new QrCode($code_url);
        $qrCode->setSize(298);

        $path =$file_path.$file_name;
        $qrCode->writeFile($path);
        return $respons->json(['code' => 200, 'message' => '登陆成功', 'data' =>config('domain_url').'storage/image/'.$file_name]);


    }


}
