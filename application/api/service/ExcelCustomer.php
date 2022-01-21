<?php


namespace app\api\service;


use app\api\controller\Base;
use app\api\service\token\LoginToken;
use LinCmsTp5\admin\model\LinUser;
use LinCmsTp5\exception\BaseException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\DateFormatter;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use think\Exception;
use think\facade\Env;

class ExcelCustomer
{
    public static function setImportData()
    {
        $base = new Base();
        $data = self::commonImportData(2);
        if(!$data) {
            return writeJson(201, [], '导入失败');
        }
        $insertData = [];
        $followData = [];
        $mainData = [];
        foreach ($data as $key => $datum) {
            $linkIndex = $base->makeLinkIndex();
            // 连接编码
            $insertData[$key]['link_code'] = $linkIndex;
            // 跟进状态
            $insertData[$key]['follow_status'] = $datum['A'];
            // 咨询日期
            $datum['B'] = DateFormatter::format($datum['B'],'YYYY-m-d');
            $insertData[$key]['create_time'] = $datum['B'];
            // 跟进业务员名和业务员id
            $token = LoginToken::getInstance();
            $author = $datum['C'] | $token->getCurrentUserName();
            $insertData[$key]['author'] = $author;
            // 业务员id
            $user_id = self::getUserID($author);
            $insertData[$key]['user_id'] = $user_id;
            // 客户编号
            $code = json_decode($base->makeCustomerCode()->getContent(),true);
            $user_code = $datum['D'] | $code['code'];
            $insertData[$key]['user_code'] = $user_code;
            // 客户来源
            $insertData[$key]['channel'] = $datum['E'];
            // 地址
            if($datum['F']) {
                if( !$datum['G'] ) $datum['G'] = '';
                if(!strpos($datum['F'], '省')) $datum['F'] .= '省';
                if(!strpos($datum['G'], '市')) $datum['G'] .= '市';
                $insertData[$key]['address'] = [$datum['F'],$datum['G']];
            }
//            $insertData[$key]['address'] = json([$datum['F'],$datum['G']]);
            // 联系人
            $insertData[$key]['contacts_name'] = $datum['H'];
            // 联系方式
            $insertData[$key]['telephone'] = $datum['I'];
            // 客户类型
            $insertData[$key]['customer_type'] = $datum['J'];
            // 客户名称
            $insertData[$key]['name'] = $datum['K'];
//            $insertData[$key]['purpose'] = $datum['E'];

            // 项目跟进
            // 连接编码
            $followData[$key]['link_code'] = $linkIndex;
            // 业务员id
            $followData[$key]['user_id'] = $user_id;
            // 客户咨询/客户描述
            $followData[$key]['demand_desc'] = $datum['L'];
            // 跟进次数
            $followData[$key]['follow_count'] = $datum['M'];
            // 丢单原因
            $followData[$key]['reason'] = $datum['N'];
            // 使用场景
            $followData[$key]['scene'] = $datum['O'];
            // 行业
            $followData[$key]['industry'] = $datum['P'];
            // 客户背景
            $followData[$key]['demand_bg'] = $datum['U'];
            // 提供对应解决方案
            $followData[$key]['solution'] = $datum['V'];
            // 工程安装解决方案
            $followData[$key]['install_solution'] = $datum['W'];
            // 客户关注产品亮点
            $followData[$key]['product_lights'] = $datum['X'];
            // 客户价值
            $followData[$key]['custom_value'] = $datum['Y'];
            // 业务跟进困难点
            $followData[$key]['follow_difficulty'] = $datum['Z'];
            // 客户反馈
            $followData[$key]['custom_feedback'] = $datum['AA'];
            // 产品类型
            $followData[$key]['product_type'] = $datum['Q'];
            // 产品规格
            $followData[$key]['product_spec'] = $datum['R'];
            // 数量
            $followData[$key]['product_num'] = $datum['S'];
            // 报价
            $followData[$key]['product_price'] = $datum['T'];

            // 主客户信息
            // 连接编码
            $mainData[$key]['link_code'] = $linkIndex;
            // 业务员id
            $mainData[$key]['user_id'] = $user_id;
            // 客户名称
            if(trim($datum['AB'])) {
                $mainData[$key]['main_name'] = $datum['AB'];
            } else {
                $mainData[$key]['main_name'] = '';
            }
            // 联系人
            if(trim($datum['AC'])) {
                $mainData[$key]['main_contacts'] = $datum['AC'];
            } else {
                $mainData[$key]['main_contacts'] = '';
            }
            // 手机号
            if(trim($datum['AD'])) {
                $mainData[$key]['main_tel'] = $datum['AD'];
            } else {
                $mainData[$key]['main_tel'] = '';
            }
            // 省市地址
            if($datum['AE'] && $datum['AF'] && $datum['AG']) {
                if(!strpos($datum['AE'], '省')) $datum['AE'] .= '省';
                if(!strpos($datum['AF'], '市')) $datum['AF'] .= '市';
                $mainData[$key]['main_address'] = [$datum['AE'],$datum['AF'], $datum['AG'] ];
            } else {
                $mainData[$key]['main_address'] = [];
            }
            // 具体收货地址
            if(trim($datum['AH'])) {
                $mainData[$key]['main_spec_address'] = $datum['AH'];
            } else {
                $mainData[$key]['main_spec_address'] = '';
            }
        }
        return [
            'customer' => $insertData,
            'follow' => $followData,
            'main' => $mainData
        ];
    }

    /**
     * 使用PHPEXECL导入
     *
     * @param string $file      文件地址
     * @param int    $sheet     工作表sheet(传0则获取第一个sheet)
     * @param int    $columnCnt 列数(传0则自动获取最大列)
     * @param array  $options   操作选项
     *                          array mergeCells 合并单元格数组
     *                          array formula    公式数组
     *                          array format     单元格格式数组
     *
     * @return array
     * @throws Exception
     */
    public static function commonImportData($start_row = 1)
    {
        $file = request()->file('file');
        //获取表单上传文件   限制大小20M
        $info = $file->validate([
            'size' => 20971520,
            'ext' => 'xlsx,xls'])
            ->move(Env::get('root_path') . 'public' . DS . 'excel');

        if($info) {
            $exclePath = $info->getSaveName();
            //获取文件名
            $file_name = Env::get('root_path') . 'public' . DS . 'excel/' . $exclePath;
            //获取导入数据
            $data = self::importExecl($file_name, $start_row);
            return $data;
        } else {
            return false;
        }
    }

    public static function importExecl($file = '', $start_row = 1)
    {
        $file = iconv("utf-8", "gb2312", $file);
        if (empty($file) OR !file_exists($file)) {
            throw new BaseException([
                'msg' => '文件不存在',
                'error_code' => '51004'
            ]);
        }
//      创建 Xlsx $objRead
//        $r =PHPExcel_CachedObjectStorageFactory::initialize(PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp);
//        if (!$r) {
//            die('Unable to set cell cacheing');
//        }
        $objRead = IOFactory::createReader('Xlsx');
        if (!$objRead->canRead($file)) {
            // 创建 Xls $objRead
            $objRead = IOFactory::createReader('Xls');
            if (!$objRead->canRead($file)) {
                throw new BaseException([
                    'msg' => '只支持导入Excel文件',
                    'error_code' => '51004'
                ]);
            }
        }

        // 建立excel对象
        // 加载速度很慢或者超时的时候，用这个
        $objRead->setReadDataOnly(TRUE);
        $obj = $objRead->load($file);
        // 获取指定的sheet表
        $currSheet = $obj->getSheet(0);
        /* 取得最大的列号 */
        $columnH = $currSheet->getHighestColumn();
        /* 兼容原逻辑，循环时使用的是小于等于 */
        $columnCnt = Coordinate::columnIndexFromString($columnH);
        /* 获取总行数 */
        $rowCnt = $currSheet->getHighestRow();
        $data   = [];
        /* 读取内容 */
        for ($_row = $start_row; $_row <= $rowCnt; $_row++) {
            $isNull = true;
            for ($_column = 1; $_column <= $columnCnt; $_column++) {
                $cellName = Coordinate::stringFromColumnIndex($_column);
                $cellId = $cellName . $_row;
//                $cell = $currSheet->getCell($cellId);

//                if (isset($options['format'])) {
//                    /* 获取格式 */
//                    $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
//                    /* 记录格式 */
//                    $options['format'][$_row][$cellName] = $format;
//                }

//                if (isset($options['formula'])) {
//                    /* 获取公式，公式均为=号开头数据 */
//                    $formula = $currSheet->getCell($cellId)->getValue();
//
//                    if (0 === strpos($formula, '=')) {
//                        $options['formula'][$cellName . $_row] = $formula;
//                    }
//                }

//                if (isset($format) && 'm/d/yyyy' == $format) {
//                    /* 日期格式翻转处理 */
//                    $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy/mm/dd');
//                }

                $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());

                if (!empty($data[$_row][$cellName])) {
                    $isNull = false;
                }
            }

            /* 判断是否整行数据为空，是的话删除该行数据 */
            if ($isNull) {
                unset($data[$_row]);
            }
        }
        return $data;
    }

    /**
     * Excel导出，TODO 可继续优化
     *
     * @param array  $datas      导出数据，格式['A1' => 'XXXX公司报表', 'B1' => '序号']
     * @param string $fileName   导出文件名称
     * @param array  $options    操作选项，例如：
     *                           bool   print       设置打印格式
     *                           string freezePane  锁定行数，例如表头为第一行，则锁定表头输入A2
     *                           array  setARGB     设置背景色，例如['A1', 'C1']
     *                           array  setWidth    设置宽度，例如['A' => 30, 'C' => 20]
     *                           bool   setBorder   设置单元格边框
     *                           array  mergeCells  设置合并单元格，例如['A1:J1' => 'A1:J1']
     *                           array  formula     设置公式，例如['F2' => '=IF(D2>0,E42/D2,0)']
     *                           array  format      设置格式，整列设置，例如['A' => 'General']
     *                           array  alignCenter 设置居中样式，例如['A1', 'A2']
     *                           array  bold        设置加粗样式，例如['A1', 'A2']
     *                           string savePath    保存路径，设置后则文件保存到服务器，不通过浏览器下载
     */
    public static function exportExcel(array $datas, string $fileName = '', array $options = []): bool
    {
        try {
            if (empty($datas)) {
                return false;
            }

            set_time_limit(0);
            /** @var Spreadsheet $objSpreadsheet */
            $objSpreadsheet = app(Spreadsheet::class);
            /* 设置默认文字居左，上下居中 */
            $styleArray = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ];
            $objSpreadsheet->getDefaultStyle()->applyFromArray($styleArray);
            /* 设置Excel Sheet */
            $activeSheet = $objSpreadsheet->setActiveSheetIndex(0);
            /* 设置工作表标题 */
            $objSpreadsheet->getActiveSheet(0)->setTitle('客户信息模板');


            /* 打印设置 */
            if (isset($options['print']) && $options['print']) {
                /* 设置打印为A4效果 */
                $activeSheet->getPageSetup()->setPaperSize(PageSetup:: PAPERSIZE_A4);
                /* 设置打印时边距 */
                $pValue = 1 / 2.54;
                $activeSheet->getPageMargins()->setTop($pValue / 2);
                $activeSheet->getPageMargins()->setBottom($pValue * 2);
                $activeSheet->getPageMargins()->setLeft($pValue / 2);
                $activeSheet->getPageMargins()->setRight($pValue / 2);
            }

            /* 行数据处理 */
            foreach ($datas as $aKey => $aItem) {
                foreach ($aItem as $sKey => $sItem) {
                    /* 默认文本格式 */
                    $pDataType = DataType::TYPE_STRING;

                    /* 设置单元格格式 */
                    if (isset($options['format']) && !empty($options['format'])) {
                        $colRow = Coordinate::coordinateFromString($sKey);

                        /* 存在该列格式并且有特殊格式 */
                        if (isset($options['format'][$colRow[0]]) &&
                            NumberFormat::FORMAT_GENERAL != $options['format'][$colRow[0]]) {
                            $activeSheet->getStyle($sKey)->getNumberFormat()
                                ->setFormatCode($options['format'][$colRow[0]]);

                            if (false !== strpos($options['format'][$colRow[0]], '0.00') &&
                                is_numeric(str_replace(['￥', ','], '', $sItem))) {
                                /* 数字格式转换为数字单元格 */
                                $pDataType = DataType::TYPE_NUMERIC;
                                $sItem     = str_replace(['￥', ','], '', $sItem);
                            }
                        } elseif (is_int($sItem)) {
                            $pDataType = DataType::TYPE_NUMERIC;
                        }
                    }

                    $activeSheet->setCellValueExplicit($sKey, $sItem, $pDataType);

                    /* 存在:形式的合并行列，列入A1:B2，则对应合并 */
                    if (false !== strstr($sKey, ":")) {
                        $options['mergeCells'][$sKey] = $sKey;
                    }
                }
            }
            unset($datas);

            /* 设置锁定行 */
            if (isset($options['freezePane']) && !empty($options['freezePane'])) {
                $activeSheet->freezePane($options['freezePane']);
                unset($options['freezePane']);
            }

            /* 设置宽度 */
            if (isset($options['setWidth']) && !empty($options['setWidth'])) {
                foreach ($options['setWidth'] as $swKey => $swItem) {
                    $activeSheet->getColumnDimension($swKey)->setWidth($swItem);
                }

                unset($options['setWidth']);
            }


            /* 设置背景色 */
            if (isset($options['setARGB']) && !empty($options['setARGB'])) {
                foreach ($options['setARGB'] as $sItem) {
//                    $activeSheet->getStyle($sItem)
//                        ->getFont()
//                        ->setBold(true);
                    $activeSheet->getStyle($sItem)
                        ->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB("FFE4DFEC");
                }

                unset($options['setARGB']);
            }

            /* 设置公式 */
            if (isset($options['formula']) && !empty($options['formula'])) {
                foreach ($options['formula'] as $fKey => $fItem) {
                    $activeSheet->setCellValue($fKey, $fItem);
                }

                unset($options['formula']);
            }

            /* 合并行列处理 */
            if (isset($options['mergeCells']) && !empty($options['mergeCells'])) {
                $activeSheet->setMergeCells($options['mergeCells']);
                unset($options['mergeCells']);
            }

            /* 设置居中 */
            if (isset($options['alignCenter']) && !empty($options['alignCenter'])) {
                $styleArray = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ];

                foreach ($options['alignCenter'] as $acItem) {
                    $activeSheet->getStyle($acItem)->applyFromArray($styleArray);
                }

                unset($options['alignCenter']);
            }

            /* 设置加粗 */
            if (isset($options['bold']) && !empty($options['bold'])) {
                foreach ($options['bold'] as $bItem) {
                    $activeSheet->getStyle($bItem)->getFont()->setBold(true);
                }

                unset($options['bold']);
            }

            /* 设置单元格边框，整个表格设置即可，必须在数据填充后才可以获取到最大行列 */
            if (isset($options['setBorder']) && $options['setBorder']) {
                $border    = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN, // 设置border样式
                            'color'       => ['argb' => 'FFC3CBDD'], // 设置border颜色
                        ],
                    ],
                ];
                $setBorder = 'A1:' . $activeSheet->getHighestColumn() . $activeSheet->getHighestRow();
                $activeSheet->getStyle($setBorder)->applyFromArray($border);
                unset($options['setBorder']);
            }

            $fileName = !empty($fileName) ? $fileName : (date('YmdHis') . '.xlsx');

            if (!isset($options['savePath'])) {
                /* 直接导出Excel，无需保存到本地，输出07Excel文件 */
                // 输出xlsx格式的文件  1
//                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8');
//                //告诉浏览器输出文件的名称
//                header(
//                    "Content-Disposition:attachment;filename=" . iconv(
//                        "utf-8", "GBK", $fileName
////                        "utf-8", "GB2312//TRANSLIT", $fileName
//                    )
//                );
//                // If you're serving to IE 9, then the following may be needed
//                header('Cache-Control: max-age=0');//禁止缓存

                // 2
//                header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
//                header('content-type:application/octet-stream');
//                header('Cache-Control: max-age=0');
//                header("Content-Type:application/force-download");
//                header("Content-Type:application/download");
//                header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');

                header('pragma:public');
                header('Content-type:application/vnd.ms-excel;charset=utf-8;');
                header("Content-Disposition:attachment;filename=$fileName.xls");
                $savePath = 'php://output';
            } else {
                $savePath = $options['savePath'];
            }

            ob_clean();
            ob_start();
            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
            // php://output可以直接导出xlsx文件，并且不会在服务器上生成xlsx文件
            $objWriter->save($savePath);
            /* 释放内存 */
            $objSpreadsheet->disconnectWorksheets();
            unset($objSpreadsheet);
            ob_end_flush();

            return true;
        } catch (Exception $e) {
            var_dump($e);
            return false;
        }
    }

    public static function getUserID($username="")
    {
        $id = LinUser::where('username',$username)->value('id');
        if(!$id) {
            throw new Exception('业务员不存在，请先创建业务员信息');
        }
        return $id;
    }
}