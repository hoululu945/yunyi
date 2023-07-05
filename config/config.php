<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

return [
    'app_name' => env('APP_NAME', 'skeleton'),
    'app_env' => env('APP_ENV', 'dev'),
    'scan_cacheable' => env('SCAN_CACHEABLE', false),
    StdoutLoggerInterface::class => [
        'log_level' => [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::DEBUG,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
        ],
    ],
    'status'=>[
        'failed'=>1,
        'success'=>0,
    ],
    'message'=>[
        'failed'=>'请求失败',
        'success'=>'请求成功'
    ],
    'root_path'=>'/var/www/hyperf-skeleton',
    "alisms"=>[
        'AccessKeyId'=>'LTAIY7P1Yo3vJlkp',
        'AccessKeySecret'=>'terXpm3mfSMYmAJfbAvyWpuAanPSQe',
        'SignName'=>'碎片联盟',
        'TemplateCode'=>'SMS_205825431',
        'regionId'=>'cn-hangzhou',



    ],
    'domain'=>'http://39.101.193.254/',

    'default_role_id'=>1,
    'system_role'=>1,
    'limit_design_role'=>3,
    'company_design_role'=>4,
    'company_admin_role'=>7,

    'company_service_role'=>4,



'default_company_days'=>7,


    "WX_APPID" => "wxb54d93e13f519228",
    "WX_SECRET" => "a899a67dbb51565c44c2dde7e9104c44",
    'token_name' => 'accessToken',
    'domain_url'=> 'http://www.yybxk.net/',

];
