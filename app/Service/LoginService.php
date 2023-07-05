<?php


namespace App\Service;


use _HumbugBox39a196d4601e\Nette\Neon\Exception;
use Hyperf\HttpServer\Contract\RequestInterface;

class LoginService
{
    static function upload(RequestInterface $request)
    {

        $path_storage = config('root_path').'/storage/image/';
        $file = $request->file('file');
        echo $file->getPath();
        echo $file->getRealPath();
//        var_dump($file);
//        $path = $request->file('photo')->getPath();

        /**
         * 判断接收文件是否为空
         */
        if ($file == null) {
            throw new Exception('图片为空');
//            return false;
        }
//        $request->file('file')->getClientFilename()
        $extension = $request->file('file')->getExtension();
        /**
         * 判断上传文件是否合法
         * 判断截取上传文件名是否为
         * jpeg，jpg，png其中之一
         */
        if (!in_array($extension, array("jpeg", "jpg", "png"))) {
//            return false;
            throw new Exception('图片格式不符合要求');

        }
        $size = $request->file('file')->getSize();
        if ($size > 410241024) {
            throw new Exception('图片尺寸超过限制');
        }
//        $file = $request->file('photo');
//       return [$file,$extension,$size,$path];
        $image_name = uniqid().'.'.$extension;
        $file->moveTo($path_storage.$image_name);


// 通过 isMoved(): bool 方法判断方法是否已移动
        if ($file->isMoved()) {
            return config('domain').'storage/image/'.$image_name;
        }
        throw new Exception('上传失败');

    }
}