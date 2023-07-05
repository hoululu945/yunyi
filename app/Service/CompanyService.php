<?php


namespace App\Service;


use App\Common\Tool\RedisUtil;
use App\Model\UserCompany;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Di\Annotation\Inject;

class CompanyService
{
    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;


    function level($company_id)
    {
//        $redis = RedisUtil::getInstance();
//        $user_info = $this->request->user_info;
        $level = 0;
        $company_info = UserCompany::where('id', $company_id)->first();
        if ($company_info->level == 3) {
//            $redis->setKeys('level_userid_' . $user_info['id'], 3);
            $level = $company_info->level;
        } else {
            if ($company_info->expire_time < date("Y-m-d H:i:s")) {
                $level =  0;

            } else {
                $level = $company_info->level;

            }

        }


        return $level;


    }


}