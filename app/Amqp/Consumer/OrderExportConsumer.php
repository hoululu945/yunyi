<?php


namespace App\Amqp\Consumer;
use App\Common\Tool\ExcelBom;
use App\Model\OptionLog;
use App\Model\UserOrder;
use App\Model\UserOrderFile;
use App\Model\UserOrderGroup;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\DbConnection\Db;
use PhpAmqpLib\Message\AMQPMessage;

//
/**
 * @Consumer(exchange="yunyi", routingKey="excel", queue="excel", name ="DemoConsumer", nums=1)
 */

class OrderExportConsumer extends ConsumerMessage
{
    protected $type = Type::DIRECT;
    public  function consumeMessage($data, AMQPMessage $message): string
    {
        echo '@**********************************excelexcelexcelexcel******************************************************************';

        $this->saveFileLocal($data['order_id']);
        return Result::ACK;

    }

    function zipDir($dir, $order_id)
    {
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
    }

    function saveFileLocal($order_id)
    {

        $file = UserOrderFile::where(['order_id' => $order_id,'status'=>0])->get()->toArray();
        $dir = BASE_PATH . '/storage/file/order_' . $order_id;
        if (is_dir($dir)) {
            $this->deldir($dir . '/');
        }
        mkdir($dir, 0777, true);
        foreach ($file as $k => $v) {
            $image = file_get_contents($v['file_url']);
            file_put_contents($dir . '/' . $v['name'], $image); //Where to save the image

        }
//
//
        $ex = new ExcelBom();
        $ex->exportExcel($order_id, $dir);

        $this->zipDir($dir, $order_id);

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

}