<?php


namespace App\Common\Tool;


use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use Endroid\QrCode\Writer\PngWriter;


trait QrCodeProduce
{
    function getCode($company_id,$user_id)
    {

        $file_name = md5('admin'.$company_id.$user_id).'.png';
        $url_path = '/storage/image/';
        $file_path = config('root_path').$url_path;
        $code_url =config('domain_url').'api/wechat/index?company_id='.$company_id.'&user_id='.$user_id;
        if(is_file($file_path.$file_name)) {
            unlink ($file_path.$file_name);
        }
        is_dir($file_path) OR mkdir($file_path, 0777, true);

        $set_log = true;
        $qrCode = new QrCode($code_url);
        $qrCode->setSize(298);
        if($set_log ==true){
            if(!empty($logo)){

            }


        }
        $path =$file_path.$file_name;
        $qrCode->writeFile($path);

        return config('domain_url').'storage/image/'.$file_name;

    }


}