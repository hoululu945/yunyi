<?php


namespace App\Controller;

use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use App\Amqp\Producer\DemoProducer;
use App\Amqp\Producer\OrderProducer;
use App\Common\Controller\BaseController;
use App\Common\Tool\ExcelBom;
use App\Common\Tool\HyberfRedis;
use App\Common\Tool\ImgeDown;
use App\Common\Tool\RedisUtil;
use App\Model\AuthRoleRelate;
use App\Model\AuthRoleRule;
use App\Model\AuthRule;
use App\Model\BandDui;
use App\Model\BandRecord;
use App\Model\CompanyUserRelate;
use App\Model\DesignWriteDetail;
use App\Model\OrderDetail;
use App\Model\OrderFieldVersion;
use App\Model\OrderVersion;
use App\Model\PlatemakingWriteDetail;
use App\Model\StyleParam;
use App\Model\SumBand;
use App\Model\User;
use App\Model\UserCompany;
use App\Model\UserOrder;
use App\Model\UserOrderDiscuss;
use App\Model\UserOrderFile;
use App\Model\UserOrderGroup;
use App\Model\UserOrderInfo;
use App\Model\UserOrderTalk;
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
use App\Amqp\Producer\OptionLogProducer;
use Hyperf\Amqp\Producer;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

//

/**
 * Class MenuController
 * @package App\Controller
 * @AutoController(prefix="/api/down")
 */
class DownLoadController extends BaseController
{


    public function index(ResponseInterface $response): Psr7ResponseInterface
//    public function index(ResponseInterface $response)

    {
        $param = $this->request->all();
        $order_id = 1;
        $user_id = 2;
        $is_need = UserOrderGroup::where(['user_id' => $user_id, 'order_id' => $order_id, 'type' => 1])->first();
        if (empty($is_need)) {

        }

        if (empty($param['file_id'])) {
            return $this->error('参数缺失');
        }
        $file_info = UserOrderFile::where('id', $param['file_id'])->first()->toArray();
//        return $response->download($file_info['file_url'], $file_info['name']);
//        return $response->download('https://cdn.yybxk.net/gh_c834b77f9e56_258_son2021-06-16-11-30-05.jpg', 'sss.txt');
        $image = file_get_contents($file_info['file_url']);

        file_put_contents(BASE_PATH . '/storage/file/' . $file_info['name'], $image); //Where to save the image
        return $response->download(BASE_PATH . '/storage/file/' . $file_info['name'], $file_info['name']);


    }

    function oo()
    {
        $oss_url = 'oss_url';
        $file_path = 'xxx';
        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'decode_content' => false,
        ]);
        $client->get($oss_url, [
            'sink' => $file_path,
        ]);
    }

    function headerLoad()
    {
        return $this->response->redirect('http://www.yybxk.net/api/down/index?file_id=72');
    }

    function load()
    {


        $file_name = "https://cdn.yybxk.net/gh_c834b77f9e56_258_son2021-06-16-11-30-05.jpg";//需要下载的文件

        $file_name = iconv("utf-8", "gb2312", "$file_name");

        $fp = fopen($file_name, "r+");//下载文件必须先要将文件打开，写入内存

        if (!file_exists($file_name)) {//判断文件是否存在

            echo "文件不存在";

            exit();

        }

        $file_size = filesize("https://cdn.yybxk.net/gh_c834b77f9e56_258_son2021-06-16-11-30-05.jpg");//判断文件大小

//返回的文件

        Header("Content-type: application/octet-stream");

//按照字节格式返回

        Header("Accept-Ranges: bytes");

//返回文件大小

        Header("Accept-Length: " . $file_size);

//弹出客户端对话框，对应的文件名

        Header("Content-Disposition: attachment; filename=" . $file_name);

//防止服务器瞬时压力增大，分段读取

        $buffer = 1024;

        while (!feof($fp)) {

            $file_data = fread($fp, $buffer);

            echo $file_data;

        }

//关闭文件

        fclose($fp);


    }

    function getBomUrl()
    {
        $ex = new ExcelBom();
        $url = $ex->export();
        return $this->response->download($url);
//        return $this->success('返回成功', $url);
    }

    function deldir($path)
    {
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            //如果 $p 中有两个以上的元素则说明当前 $path 不为空
            if (count($p) > 2) {
                foreach ($p as $val) {
                    //排除目录中的.和..
                    if ($val != "." && $val != "..") {
                        //如果是目录则递归子目录，继续操作
                        if (is_dir($path . $val)) {
                            //子目录中操作删除文件夹和文件
                            $this->deldir($path . $val . '/');
                        } else {
                            //如果是文件直接删除
                            unlink($path . $val);
                        }
                    }
                }
            }
        }
        //删除目录
        return rmdir($path);
    }

    function addFileToZip($path, $zip)
    {
        $handler = opendir($path); //打开当前文件夹由$path指定。
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {//文件夹文件名字为'.'和‘..'，不要对他们进行操作
                if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归
                    $this->addFileToZip($path . "/" . $filename, $zip);
                } else { //将文件加入zip对象
                    $zip->addFile($path . "/" . $filename, $filename);
                }
            }
        }
        @closedir($path);
    }

    function zipDir($dir, $order_id)
    {
        $zip_path = BASE_PATH . '/storage/file/order_zip';
        if (!is_dir($zip_path)) {
//            rmdir($dir);
            mkdir($zip_path, 0777, true);
        }
//        if (!file_exists($dir)) {
        $zip_name = $zip_path . '/' . $order_id . '_order.zip';
        if (is_file($zip_name)) {
            unlink($zip_name);
        }
        $path = $dir;
        $zip = new \ZipArchive();
        if ($zip->open($zip_name, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) === TRUE) {

            if (is_dir($path)) { //给出文件夹，打包文件夹
                echo 22222222;
                $this->addFileToZip($path, $zip);
            } else if (is_array($path)) { //以数组形式给出文件路径
                foreach ($path as $file) {
                    $zip->addFile($file);
                }
            } else {   //只给出一个文件
                $zip->addFile($path);
            }
            echo 3333333;
            $zip->close(); //关闭处理的zip文件

        }
    }

    function saveFileLocal()
    {
        $param = $this->request->all();
        if (empty($param['order_id'])) {
            return false;
        }
        $order_id = $param['order_id'];
        $file = UserOrderFile::where(['order_id' => $order_id])->get()->toArray();
        $dir = BASE_PATH . '/storage/file/order_' . $order_id;
        if (is_dir($dir)) {
//            rmdir($dir);
            $this->deldir($dir . '/');
        }
//        if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
//        }
        foreach ($file as $k => $v) {
            $image = file_get_contents($v['file_url']);

            file_put_contents($dir . '/' . $v['name'], $image); //Where to save the image

        }
//
//
        $ex = new ExcelBom();
//        $url = $ex->export();
        $exc_res = $ex->exportExcel($order_id, $dir);

        $this->zipDir($dir, $order_id);

    }

    function del1Dir()
    {
//        if(is_file( BASE_PATH .'/zeus920.zip')){
//            unlink(BASE_PATH .'/zeus920.zip');
//            return $this->error();
//        }
//        return $this->success();
        $dir = BASE_PATH . '/storage/image';

        $this->zipDir($dir, 1);
    }

    function downZip(ResponseInterface $response){
        $param = $this->request->all();
        if(empty($param['order_id'])){
            return $this->error('参数缺失');
        }
        $zip_path = BASE_PATH . '/storage/file/order_zip';

//        if (!file_exists($dir)) {
        $zip_name = $zip_path . '/' .$param['order_id'] . '_order.zip';
        if(!is_file($zip_name)){
            return $this->error('下载失败');
        }
        return $response->download($zip_name,$param['order_id'] .time(). '_order.zip');

    }


    function zipDir1()
    {
        $order_id = 3;

        $dir = BASE_PATH . '/storage/file/order_' . $order_id;

        $zip_path = BASE_PATH . '/storage/file/order_zip';
        if (!is_dir($zip_path)) {
            mkdir($zip_path, 0777, true);
        }
        $zip_name = $zip_path . '/' . $order_id . '_order.zip';
        if (is_file($zip_name)) {
            unlink($zip_name);
        }
        $path = $dir;
        $zip = new \ZipArchive();
        if ($zip->open($zip_name, \ZipArchive::OVERWRITE | \ZipArchive::CREATE) === TRUE) {

            if (is_dir($path)) { //给出文件夹，打包文件夹
                $this->addFileToZip($path, $zip);
            } else if (is_array($path)) { //以数组形式给出文件路径
                foreach ($path as $file) {
                    $zip->addFile($file);
                }
            } else {   //只给出一个文件
                $zip->addFile($path);
            }
            $zip->close(); //关闭处理的zip文件

        }

        return $this->success();
    }

}