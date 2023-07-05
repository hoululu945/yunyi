<?php


namespace App\Common\Tool;

use App\Model\OrderDetail;
use App\Model\OrderFieldVersion;
use App\Model\OrderVersion;
use App\Model\StoreDetail;
use App\Model\StoreFieldVersion;
use App\Model\SystemType;
use App\Model\UserOrder;
use App\Model\VersionField;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;

class ExcelBom
{
    protected $spreadsheet;
    protected $sheet;

    /**
     * @Inject
     * @var HttpResponse
     */
    protected $response;

//    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
//    {
//        $this->container = $container;
//        $this->response = $response;
//        $this->request = $request;
//    }
    function __construct()
    {
//        $this->response = $response;

        $this->spreadsheet = new Spreadsheet();

        $this->sheet = $this->spreadsheet->getActiveSheet();

    }

    function export()
    {
        $data = SystemType::select('type_name', 'name')->get()->toArray();
//        $data = [
//            ['title1' => '111', 'title2' => '222'],
//            ['title1' => '111', 'title2' => '222'],
//            ['title1' => '111', 'title2' => '222']
//        ];
        $title = ['属性名', '属性值'];
        foreach ($title as $k => $item) {
            $column[$k] = $this->intToChr($k);
        }
//        foreach ($column as $key => $value) {
//            // 单元格内容写入
//            $this->sheet->setCellValueByColumnAndRow($key + 1, 1, $value);
//        }
        foreach ($title as $key => $value) {
            // 单元格内容写入
            $this->sheet->setCellValueByColumnAndRow($key + 1, 1, $value);
        }
        $row = 2; // 从第二行开始
        foreach ($data as $item) {
            $column = 1;
            foreach ($item as $value) {
                // 单元格内容写入
                $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        }
//        $this->sheet->setCellValue('A1', 'ID');
//        $this->sheet->setCellValue('B1', '姓名');
//        $this->sheet->setCellValue('C1', '年龄');
//        $this->sheet->setCellValue('D1', '身高');
//
//        $this->sheet->setCellValueByColumnAndRow(1, 2, 1);
//        $this->sheet->setCellValueByColumnAndRow(2, 2, '李雷');
//        $this->sheet->setCellValueByColumnAndRow(3, 2, '18岁');
//        $this->sheet->setCellValueByColumnAndRow(4, 2, '188cm');
//
//        $this->sheet->setCellValueByColumnAndRow(1, 3, 2);
//        $this->sheet->setCellValueByColumnAndRow(2, 3, '韩梅梅');
//        $this->sheet->setCellValueByColumnAndRow(3, 3, '17岁');
//        $this->sheet->setCellValueByColumnAndRow(4, 3, '165cm');
//        $this->sheet->getStyle('B2')->getFont()->setBold(true)->setName('宋体')->setSize(20);
//        $this->sheet->getStyle('B2')->getFont()->getColor()->setRGB('#AEEEEE');
//        $this->sheet->getStyle('B3')->getFont()->getColor()->setARGB('FFFF0000');
//        $this->sheet->setCellValue('A1', '2019-10-10 10:10:10');
//        $this->sheet->setCellValue('A2', '2019-10-10 10:10:10');
//        $this->sheet->getStyle('A2')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);

        # Xlsx类 将电子表格保存到文件
//        $this->spreadsheet->getProperties()->setCreated()
        $writer = new Xlsx($this->spreadsheet);
        $file_path = '../' . uniqid() . '.xlsx';
        $writer->save($file_path);
//       return  $this->response->download($file_path,'aaa.txt');
        return $file_path;
//        $qiniu = new qiniu();
//        $url = $qiniu->uploadPath($file_path);

//       return $qiniu->uploadPath($file_path);

    }

    public function intToChr($index, $start = 65)
    {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= $this->intToChr(floor($index / 26) - 1);
        }

        return $str . chr($index % 26 + $start);
    }

    function readFile($path)
    {
        $inputFileName = $path;
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
        // 方法二
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        return $sheetData;
    }



    function exportExcel($order_id,$dir)
    {
//        $order_id = $o;
        $order_info = UserOrder::where('id', $order_id)->first();
        $order_version = OrderVersion::where(['order_id'=>$order_id])->orderByDesc('id')->first();
        if(empty($order_version)){
            return false;
        }
      echo  OrderFieldVersion::where(['order_id' => $order_id,'version_no'=>$order_version->version_num])->toSql();
      echo $order_version->version_num;
        $dv_list = OrderFieldVersion::where(['order_id' => $order_id,'version_no'=>$order_version->version_num])->get()->toArray();
//        var_dump($dv_list);
//        $where['company_id'] = $company_id;
//        foreach ($dv_list as $k => $v) {
//            $where['version_num'] = $v['version_num'];
//            $where['type'] = $v['type'];
//            $where['status'] = 0;
//            var_dump($where);
//            $dv_list[$k]['filed_list'] = CompanyFieldSet::where($where)->select('field_name', 'field_name_py')->get()->toArray();
//        }
        foreach ($dv_list as $k => $v) {
            $where['version_id'] = $v['version_id'];
//            $where['type'] = $v['type'];
            $where['status'] = 0;
//            var_dump($where);
//            $dv_list[$k]['filed_list'] = CompanyFieldSet::with(['versionField'=>function($query){
//                $query->where('status',0);
//            }])->where($where)->select('id', 'version_num')->get()->toArray();
            $dv_list[$k]['filed_list'] = VersionField::where($where)->select('field_name','id as field_name_py')->get()->toArray();
        }
//        var_dump($dv_list);
        $order_detail = OrderDetail::where(['order_id' => $order_id])->orderByDesc('id')->first();

        if (!empty($order_detail->content)) {
            $order_detail->content = unserialize($order_detail->content);
        }
        $data = $order_detail->content;
        $row = 1;

        foreach ($dv_list as $v){
//            foreach ($v['filed_list'] as $kf=>$vf) {
//                $column[$k] = $this->intToChr($kf);
//            }
            foreach ($v['filed_list'] as $kfi=>$vfi) {
                // 单元格内容写入
                $this->sheet->setCellValueByColumnAndRow($kfi + 1, $row, $vfi['field_name']);
            }
            $row = $row+1; // 从第二行开始
            if($v['type']==1){
                foreach ($data['design'] as $item) {
                    $column = 1;
                    foreach ($item as $value) {
                        // 单元格内容写入
                        $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                        $column++;
                    }
                    $row++;
                }
            }
            if($v['type']==2){
                foreach ($data['zb'] as $item) {
                    $column = 1;
                    foreach ($item as $value) {
                        // 单元格内容写入
                        $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                        $column++;
                    }
                    $row++;
                }
            }
            if($v['type']==3){
                foreach ($data['zy'] as $item) {
                    $column = 1;
                    foreach ($item as $value) {
                        // 单元格内容写入
                        $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                        $column++;
                    }
                    $row++;
                }
            }
            $row++;

        }
        echo '*****************';
        echo $row;
        $writer = new Xlsx($this->spreadsheet);
        $file_path = $dir.'/' . uniqid() . '.xlsx';
        $writer->save($file_path);
//       return  $this->response->download($file_path,'aaa.txt');
        return $file_path;

//        var_dump($order_detail->content);

//        $data = [
//            ['title1' => '111', 'title2' => '222'],
//            ['title1' => '111', 'title2' => '222'],
//            ['title1' => '111', 'title2' => '222']
//        ];
//        $title = ['属性名', '属性值'];
//        foreach ($title as $k => $item) {
//            $column[$k] = $this->intToChr($k);
//        }
////        foreach ($column as $key => $value) {
////            // 单元格内容写入
////            $this->sheet->setCellValueByColumnAndRow($key + 1, 1, $value);
////        }
//        foreach ($title as $key => $value) {
//            // 单元格内容写入
//            $this->sheet->setCellValueByColumnAndRow($key + 1, 1, $value);
//        }
//        $row = 2; // 从第二行开始
//        foreach ($data as $item) {
//            $column = 1;
//            foreach ($item as $value) {
//                // 单元格内容写入
//                $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
//                $column++;
//            }
//            $row++;
//        }
////        $this->sheet->setCellValue('A1', 'ID');
////        $this->sheet->setCellValue('B1', '姓名');
////        $this->sheet->setCellValue('C1', '年龄');
////        $this->sheet->setCellValue('D1', '身高');
////
////        $this->sheet->setCellValueByColumnAndRow(1, 2, 1);
////        $this->sheet->setCellValueByColumnAndRow(2, 2, '李雷');
////        $this->sheet->setCellValueByColumnAndRow(3, 2, '18岁');
////        $this->sheet->setCellValueByColumnAndRow(4, 2, '188cm');
////
////        $this->sheet->setCellValueByColumnAndRow(1, 3, 2);
////        $this->sheet->setCellValueByColumnAndRow(2, 3, '韩梅梅');
////        $this->sheet->setCellValueByColumnAndRow(3, 3, '17岁');
////        $this->sheet->setCellValueByColumnAndRow(4, 3, '165cm');
////        $this->sheet->getStyle('B2')->getFont()->setBold(true)->setName('宋体')->setSize(20);
////        $this->sheet->getStyle('B2')->getFont()->getColor()->setRGB('#AEEEEE');
////        $this->sheet->getStyle('B3')->getFont()->getColor()->setARGB('FFFF0000');
////        $this->sheet->setCellValue('A1', '2019-10-10 10:10:10');
////        $this->sheet->setCellValue('A2', '2019-10-10 10:10:10');
////        $this->sheet->getStyle('A2')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);
//
//        # Xlsx类 将电子表格保存到文件
////        $this->spreadsheet->getProperties()->setCreated()
//        $writer = new Xlsx($this->spreadsheet);
//        $file_path = '../' . uniqid() . '.xlsx';
//        $writer->save($file_path);
////       return  $this->response->download($file_path,'aaa.txt');
//        return $file_path;
//        $qiniu = new qiniu();
//        $url = $qiniu->uploadPath($file_path);

//       return $qiniu->uploadPath($file_path);

    }

    function exportStoreExcel($order_id,$dir)
    {

        $dv_list = StoreFieldVersion::where(['store_order_id' => $order_id])->get()->toArray();

        foreach ($dv_list as $k => $v) {
            $where['version_id'] = $v['version_id'];
            $where['status'] = 0;
            $dv_list[$k]['filed_list'] = VersionField::where($where)->select('field_name','id as field_name_py')->get()->toArray();
        }
//        var_dump($dv_list);
        $order_detail = StoreDetail::where(['store_order_id' => $order_id])->orderByDesc('id')->first();

        if (!empty($order_detail->content)) {
            $order_detail->content = unserialize($order_detail->content);
        }
        $data = $order_detail->content;
        $row = 1;

        foreach ($dv_list as $v){
            foreach ($v['filed_list'] as $kfi=>$vfi) {
                // 单元格内容写入
                $this->sheet->setCellValueByColumnAndRow($kfi + 1, $row, $vfi['field_name']);
            }
            $row = $row+1; // 从第二行开始
            if($v['type']==1){
                foreach ($data['design'] as $item) {
                    $column = 1;
                    foreach ($item as $value) {
                        // 单元格内容写入
                        $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                        $column++;
                    }
                    $row++;
                }
            }
            if($v['type']==2){
                foreach ($data['zb'] as $item) {
                    $column = 1;
                    foreach ($item as $value) {
                        // 单元格内容写入
                        $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                        $column++;
                    }
                    $row++;
                }
            }
            if($v['type']==3){
                foreach ($data['zy'] as $item) {
                    $column = 1;
                    foreach ($item as $value) {
                        // 单元格内容写入
                        $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
                        $column++;
                    }
                    $row++;
                }
            }
            $row++;

        }
        echo '*****************';
        echo $row;
        $writer = new Xlsx($this->spreadsheet);
        $file_path = $dir.'/' . uniqid() . '.xlsx';
        $writer->save($file_path);
//       return  $this->response->download($file_path,'aaa.txt');
        return $file_path;



    }


}