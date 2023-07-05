<?php


namespace App\Common\Tool;

//require '/Users/shiyu/workspace/hyperf-skeleton/vendor/autoload.php';

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Hyperf\Di\Annotation\Inject;

class qiniu
{
    /**@Inject
     * @var ResponseInterface
     */
    private $response;
    static private $instance;
    //防止使用clone克隆对象
    private function __clone(){}


    static public function getInstance()
    {
        //判断$instance是否是Singleton的对象，不是则创建
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function upload(RequestInterface $request,ResponseInterface $response){
        $accessKey = 'eLkmT9dVkka0RAEVZ8o62AErI3xdAbz9gHYPR5R3';
        $secretKey = 'c4TVoT6TqedRPWwPIkv4FUxaOoVO_HDbGUzFOOJ1';
        $auth = new Auth($accessKey, $secretKey);
        $bucket = 'bxk';
        $domain = 'http://cdn.yybxk.net/';
// 生成上传Token
        $token = $auth->uploadToken($bucket);
// 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
//        $file = $request->file();
        $file = $request->file('file');
        $old_name = $file->getClientFilename();
        $name_arr = explode('.',$old_name);
        $name = $name_arr[0];
        $file_Path = $file->getRealPath();
        $ext = $file->getExtension();
//        $key =substr(md5($file_Path) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
        $key =$name.'_'.$request->user_info['userName'].date("Y-m-d-H-i-s").'.' . $ext;

        list($ret, $err) = $uploadMgr->putFile($token, $key, $file_Path);
        if ($err !== null) {
            return $response->json(["code"=>-1,"mmessagesg"=>$err,"data"=>""]);
        } else {
            //返回图片的完整URL
            return $response->json(["code"=>200,"message"=>"上传完成","data"=>($domain . $ret['key'])]);
        }

    }
    function uploadPath($path){

        $accessKey = 'eLkmT9dVkka0RAEVZ8o62AErI3xdAbz9gHYPR5R3';
        $secretKey = 'c4TVoT6TqedRPWwPIkv4FUxaOoVO_HDbGUzFOOJ1';
        $auth = new Auth($accessKey, $secretKey);
        $bucket = 'bxk';
        $domain = 'https://cdn.yybxk.net/';
// 生成上传Token
        $token = $auth->uploadToken($bucket);
// 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
//        $file = $request->file();
        $file_path = $path;
        $key =substr(md5($file_path) , 0, 5). date('YmdHis') . rand(0, 9999) . '.xlsx';

        list($ret, $err) = $uploadMgr->putFile($token, $key, $file_path);
//        if ($err !== null) {
//            return $response->json(["code"=>-1,"mmessagesg"=>$err,"data"=>""]);
//        } else {
//            //返回图片的完整URL
//            return $response->json(["code"=>200,"message"=>"上传完成","data"=>($domain . $ret['key'])]);
//        }
        unlink($file_path);

        return $domain . $ret['key'];

    }



}
