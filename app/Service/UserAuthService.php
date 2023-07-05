<?php


namespace App\Service;


use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Model\User;
use App\Model\UserInfo;

class UserAuthService
{


    /**
     * 生成格式化的密码
     *
     * @param $password
     * @return string
     */
    public static function generateFormattedPassword($password)
    {
        return strtoupper(md5(md5($password)));
    }

    static function get_client_ip()
    {
        $ip = null;
//        echo $_SERVER['REMOTE_ADDR'];
//        echo $_SERVER['HTTP_CLIENT_IP'];
        echo $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $_SERVER['X-Real-IP'];


//        if (isset($_SERVER['X-Real-IP'])) {
//            $ip = explode(',', $_SERVER['X-Real-IP']);
//            $ip = trim(current($ip));
//        }
//        return $ip;
    }
    static function getip()
    {

        static $ip = '';

        $ip = empty($_SERVER['REMOTE_ADDR'])?'127.0.0.1':$_SERVER['REMOTE_ADDR'];

        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {

            $ip = $_SERVER['HTTP_CDN_SRC_IP'];

        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {

            $ip = $_SERVER['HTTP_CLIENT_IP'];

        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {

            foreach ($matches[0] as $xip) {

                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {

                    $ip = $xip;

                    break;

                }

            }

        }

        return $ip;

    }


    public static function authorizeByPassword($userNo, $password, $lastLoginIp = '', $deviceType = 'pc')
    {
        // 暂时不做账号有效性验证
        $user = User::query()->where(['userName' => $userNo])->first();
//        var_dump($user);
        if (empty($user)) {
            $user = User::query()->where(['phone' => $userNo])->first();
            if (empty($user)) {
                throw new \Exception("登录账号不存在！");

            }
        }
        $user = $user->toArray();
//        echo self::generateFormattedPassword($password);
//        var_dump($user);
        if (self::generateFormattedPassword($password) != $user['pwd']) {
            throw new Exception("密码错误！");
        }

        /*
         * 一个管理员管理多个公司，这个暂时不需要
        $company_id_arr = AuthRole::whereHas('authRoleUser',function($q) use ($user){
            $q->where('user_id',$user->user_id);
        })->with('authRoleUser')->where('company_id','>',0)->pluck('company_id')->toArray();
        */

        // 更新登录信息
        try {
            $userInfo['last_login_date'] = date("Y-m-d H:i:s");
            $userInfo['last_login_ip'] = $lastLoginIp;
            $userInfo['user_id'] = $user['id'];
            $userBaseInfo = UserInfo::query()->where('user_id', $user['id'])->first();

            if (empty($userBaseInfo)) {
                UserInfo::query()->insert($userInfo);

            } else {
                $userBaseInfo = $userBaseInfo->toArray();
                $info = array_merge($userBaseInfo, $userInfo);
                $userInfo['user_id'];
                $result = UserInfo::query()->where('user_id', $userInfo['user_id'])->update($info);

            }
        } catch (\Exception $e) {

            return false;
        }

        return $user;
    }


}