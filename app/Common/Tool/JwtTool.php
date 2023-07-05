<?php


namespace App\Common\Tool;



use App\Model\AuthRoleRelate;
use App\Model\AuthRule;
use App\Model\CompanyUserRelate;
use App\Service\LoginService;
use App\Service\MenuService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Di\Annotation\Inject;

/**
 * PHP实现jwt
 */
trait JwtTool
{
    /**
     * @param RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @Inject
     * @var MenuService
     */
    private $menuService;

    //头部
    private static $header = array(
        'alg' => 'HS256', //生成signature的算法
        'typ' => 'JWT'    //类型
    );

    //使用HMAC生成信息摘要时所使用的密钥
    private static $key = '123456';


    /**
     * 获取jwt token
     * @param array $payload jwt载荷   格式如下非必须
     * [
     *  'iss'=>'jwt_admin',  //该JWT的签发者
     *  'iat'=>time(),  //签发时间
     *  'exp'=>time()+7200,  //过期时间
     *  'nbf'=>time()+60,  //该时间之前不接收处理该Token
     *  'sub'=>'www.admin.com',  //面向的用户
     *  'jti'=>md5(uniqid('JWT').time())  //该Token唯一标识
     * ]
     * @return bool|string
     */

    function test()
    {
        echo 123123123;
    }

    public static function getToken(array $user)
    {
        $payload_must = array('iat' => time(), 'exp' => time() + 7200*24, 'nbf' => time());

        $payload = array_merge($payload_must, $user);
        if (is_array($payload)) {
            $base64header = self::base64UrlEncode(json_encode(self::$header, JSON_UNESCAPED_UNICODE));
            $base64payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
            $token = $base64header . '.' . $base64payload . '.' . self::signature($base64header . '.' . $base64payload, self::$key, self::$header['alg']);
            return $token;
        } else {
            return false;
        }
    }

    /**
     * @param array $user
     * @return bool
     * 获取token或者token过期都返回token 和refresh_token
     */
    public static function refreshGetToken(array $user)
    {
        $access_token = self::getToken($user);
        $payload_must = array('iat' => time(), 'exp' => time() + 3600*24, 'nbf' => time());
        $payload = array_merge($payload_must,$user);
        // 'exp'=>time()+7200,  //过期时间
        $token_arr['access_token'] = $access_token;
        if (is_array($payload)) {
            $base64header = self::base64UrlEncode(json_encode(self::$header, JSON_UNESCAPED_UNICODE));
            $base64payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
            $refresh_token = $base64header . '.' . $base64payload . '.' . self::signature($base64header . '.' . $base64payload, self::$key, self::$header['alg']);
            $token_arr['refresh_token'] = $refresh_token;
        } else {
            return false;
        }
        return $token_arr;
    }


    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $Token 需要验证的token
     * @return bool|string
     *
     * access_token 和 refresh_token 都在此验证
     */
    public static function verifyToken(string $Token)
    {
        $tokens = explode('.', $Token);
        if (count($tokens) != 3)
            return false;

        list($base64header, $base64payload, $sign) = $tokens;

        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']))
            return false;

        //签名验证
        if (self::signature($base64header . '.' . $base64payload, self::$key, $base64decodeheader['alg']) !== $sign)
            return false;

        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);

        //签发时间大于当前服务器时间验证失败
        if (isset($payload['iat']) && $payload['iat'] > time())
            return false;

        //过期时间小宇当前服务器时间验证失败
        if (isset($payload['exp']) && $payload['exp'] < time())
            return false;

        //该nbf时间之前不接收处理该Token
        if (isset($payload['nbf']) && $payload['nbf'] > time())
            return false;

        return $payload;
    }


    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    private static function base64UrlEncode(string $input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * HMACSHA256签名   https://jwt.io/  中HMACSHA256签名实现
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg 算法方式
     * @return mixed
     */
    private static function signature(string $input, string $key, string $alg = null)
    {
        $alg_config = array(
            'HS256' => 'sha256'
        );
        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key, true));
    }

    function getRoleRuleName($user_id){


        $redis = RedisUtil::getInstance();
        $company_switch_id = $redis->getKeys('switch_company_'.$user_id);
        $company_switch_id = empty($company_switch_id)?0:$company_switch_id;

        $user_relate = CompanyUserRelate::query()->where(['user_id'=>$user_id,'company_id'=>$company_switch_id])->first();
        if(empty($user_relate)){
            $role_id = 0;;
        }else{
            $relate_id = $user_relate->id;
            $relate_role = AuthRoleRelate::query()->where('relate_id',$relate_id)->first();
            if(empty($relate_role)){
                $role_id = config('system_role');

            }else{
                $role_id =  $relate_role->role_id;
            }

        }

        if(empty($role_id)){
            $rule_names = AuthRule::query()->pluck('name');
//            var_dump($rule_names);
//            echo '****%%%%***';

        }else{
            $rules = $this->menuService->allRoleRule($role_id);
            $rule_names = AuthRule::query()->whereIn('id',$rules)->pluck('name');
        }

//        $rules = $this->menuService->allRoleRule($role_id);
//        $rule_names = AuthRule::query()->whereIn('id',$rules)->pluck('name');
        if(!empty($rule_names)){
            $rule_names = array_unique($rule_names->toArray());

        }
        $redis = RedisUtil::getInstance();
//        $redis->setKeys('rule_name_user'.$user_id,json_encode($rule_names));

        if(!empty($rule_names)){
            $rule_names = array_unique($rule_names->toArray());
            $redis->setKeys('rule_name_user'.$user_id,json_encode($rule_names));

        }else{
            $redis->setKeys('rule_name_user'.$user_id,json_encode([]));

        }
    }



}

