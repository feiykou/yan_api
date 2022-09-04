<?php


namespace app\api\service;


use app\api\controller\Base;
use app\api\model\Customer;
use app\api\controller\v1\Type as SettingType;
use app\api\model\CustomerProject;
use app\api\service\token\LoginToken;
use LinCmsTp5\admin\model\LinUser;
use LinCmsTp5\exception\BaseException;
use phpDocumentor\Reflection\Type;
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
use function foo\func;

class ExcelCustomer
{
    public static function setImportData()
    {
//        header("Content-type:text/html;charset=utf-8");
        $base = new Base();
        $data = self::commonImportData(2);
        if(!$data) {
            return writeJson(201, [], '导入失败');
        }
        $insertData = [];
        $followData = [];
        $mainData = [];
        $userNameArr = [];
        try {
            $typeData = (new SettingType())->getFieldValue('follow_status,status,channel');
        } catch (Exception $e) {
            $typeData = [];
        }
        if(!$typeData || count($typeData) == 0) {
            throw new Exception('请先配置类型');
        }
        $typeJson = [];
        $userCodeArr = []; // 获取excel用户编码
        foreach ($typeData as $item) {
            $field = $item['field'];
            $typeJson[$field] = $item['value'];
        }
        foreach ($data as $key => $datum) {
            $linkIndex = $base->makeLinkIndex();
            // 连接编码
            $insertData[$key]['link_code'] = $linkIndex;
            // 跟进状态  判断该状态是否在规定的状态   上传可不配置
//            if(!in_array($datum['A'],$typeJson['follow_status'])) {
//                throw new Exception('客户跟进状态未配置');
//            }
            $insertData[$key]['follow_status'] = $datum['A'];
            // 咨询日期  判断该日期是否符合要求
            try {
                $datum['B'] = DateFormatter::format($datum['B'],'YYYY-m-d');
            } catch (Exception $e) {
//                $datum['B'] = date('y-m-d H:i:s', time());
                throw new Exception('咨询日期格式错误');
            }
            $insertData[$key]['create_time'] = $datum['B'];
            $insertData[$key]['update_time'] = $datum['B'];
            // 跟进业务员名和业务员id
            $token = LoginToken::getInstance();
            $author = $datum['C'];
            if(empty($datum['C'])) {
                $author = $token->getCurrentUserName();
            }
            $insertData[$key]['author'] = $author;
            // 业务员id
            $searVal = array_search($author, $userNameArr);
            if(!$searVal) {
                $user_id = self::getUserID($author);
                $userNameArr[$user_id] = $author;
            } else {
                $user_id = $searVal;
            }

            $insertData[$key]['user_id'] = $user_id;
            // 客户编号
            $code = json_decode($base->makeCustomerCode()->getContent(),true);
            $user_code = !empty($datum['D']) ? trim($datum['D']) : '';
            if($user_code) {
                $insertData[$key]['id'] = $user_code;
                array_push($userCodeArr, $user_code);
            } else {
                throw new Exception('客户编码不能为空');
            }
            $insertData[$key]['user_code'] = $user_code;
            // 客户来源  判断客户来源
            if(!empty($datum['E'])) {
//                var_dump($datum['E']);
                if(!in_array($datum['E'],$typeJson['channel'])) {
                    throw new Exception('客户来源未配置');
                }
            }
            $insertData[$key]['channel'] = $datum['E'];
            // 地址
            $datum['F'] = preg_replace('/\s+/u', '', $datum['F']);
            $datum['G'] = preg_replace('/\s+/u', '', $datum['G']);
            if($datum['F']) {
                if( !$datum['G'] ) $datum['G'] = '';
                $singleArea = ['重庆', '北京', '天津', '上海'];
                $aloneArea = ['香港', '澳门'];
                $selfArea = ['内蒙古','西藏'];
                $areaMark = false;
                foreach ($singleArea as $val) {
                    if(strstr($datum['F'],$val)) {
                        $areaMark = true;
                        $datum['F'] = $val . '市';
                        $datum['G'] = '市辖区';
                    }
                }
                foreach ($aloneArea as $aval) {
                    if(strstr($datum['F'],$aval) && !strstr($val, '特别行政区')) {
                        $areaMark = true;
                        $datum['F'] = $val . '特别行政区';
                        $datum['G'] = '';
                    }
                }
                if(strstr($datum['F'],'广西')){
                    $areaMark = true;
                    $datum['F'] = '广西壮族自治区';
                    $datum['G'] = '';
                }
                if(strstr($datum['F'],'宁夏')){
                    $areaMark = true;
                    $datum['F'] = '宁夏回族自治区';
                    $datum['G'] = '';
                }
                if(strstr($datum['F'],'新疆')){
                    $areaMark = true;
                    $datum['F'] = '新疆维吾尔自治区';
                    $datum['G'] = '';
                }
                foreach ($selfArea as $sval) {
                    if(strstr($datum['F'],$sval)) {
                        $areaMark = true;
                        $datum['F'] = $val . '自治区';
                        $datum['G'] = '';
                    }
                }
                if(strstr($datum['F'],'台湾')) {
                    $areaMark = true;
                    $datum['F'] = '台湾省';
                    $datum['G'] = '';
                }
                if(!$areaMark) {
                    if(!strpos($datum['F'], '省')) $datum['F'] .= '省';
                    if(!$datum['G']) {
                        $datum['G'] = '全部';
                    } else {
                        if(!strpos($datum['G'], '市')) $datum['G'] .= '市';
                    }
                }
                $insertData[$key]['address'] = ['province' => $datum['F'], 'city' => $datum['G']];
            }
            if(empty($datum['F'])) {
                $insertData[$key]['address'] = [];
            }
//            $insertData[$key]['address'] = json([$datum['F'],$datum['G']]);
            // 联系人
            $insertData[$key]['contacts_name'] = $datum['H'];
            // 联系方式
            $insertData[$key]['telephone'] = trim($datum['I']);
            // 客户类型
            $insertData[$key]['customer_type'] = $datum['J'];
            // 客户名称
            $insertData[$key]['name'] = $datum['K'];
//            $insertData[$key]['purpose'] = $datum['E'];
            // 项目跟进
            // 连接编码
            $followData[$key]['link_code'] = $linkIndex;
            $followData[$key]['user_code'] = $user_code;
            $followData[$key]['customer_id'] = $user_code;

            // 业务员id
            $followData[$key]['user_id'] = $user_id;
            $followData[$key]['name'] = $datum['L'];
            // 项目状态
//            if(!in_array($datum['M'],$typeJson['status'])) {
//                throw new Exception('项目跟进状态未配置');
//            }
            $followData[$key]['follow_status'] = $datum['M'];
            // 订单编码
            $followData[$key]['order_no'] = $datum['N'];
            // 成交时间
            if(!empty($datum['N'])) {
                try{
                    $datum['O'] = DateFormatter::format($datum['O'],'YYYY-m-d');
                } catch (Exception $e) {
                    throw new Exception('成交时间格式错误');
                }
                $followData[$key]['status_success_time'] = $datum['O'];
            } else {
                $followData[$key]['status_success_time'] = null;
            }
            // 客户咨询/客户描述
            $followData[$key]['demand_desc'] = $datum['P'];
            // 跟进次数  判断数字
            if(!empty($datum['Q']) && !is_numeric($datum['Q'])) {
                throw new Exception('跟进次数必须是数字');
            }
            $followData[$key]['follow_count'] = $datum['Q'];
            // 丢单原因
            $followData[$key]['reason'] = $datum['R'];
            // 使用场景
            $followData[$key]['scene'] = $datum['S'];
            // 行业
            $followData[$key]['industry'] = $datum['T'];
            // 客户背景
            $followData[$key]['demand_bg'] = $datum['Y'];
            // 提供对应解决方案
            $followData[$key]['solution'] = $datum['Z'];
            // 工程安装解决方案
            $followData[$key]['install_solution'] = $datum['AA'];
            // 客户关注产品亮点
            $followData[$key]['product_lights'] = $datum['AB'];
            // 客户价值
            $followData[$key]['custom_value'] = $datum['AC'];
            // 业务跟进困难点
            $followData[$key]['follow_difficulty'] = $datum['AD'];
            // 客户反馈
            $followData[$key]['custom_feedback'] = $datum['AE'];
            // 产品类型
            $followData[$key]['product_type'] = $datum['U'];
            // 产品规格
            $followData[$key]['product_spec'] = $datum['V'];
            // 数量
            $followData[$key]['product_num'] = $datum['W'];
            // 报价
            $followData[$key]['product_price'] = $datum['X'];
            // 客户名
            $followData[$key]['customer_name'] = $datum['K'];
            // 项目来源  判断项目来源
            $followData[$key]['project_channel'] = $datum['AF'];

            // 主客户信息
            // 连接编码
            $mainData[$key]['link_code'] = $linkIndex;
            // 业务员id
            $mainData[$key]['user_id'] = $user_id;
            // 客户名称
            if(trim($datum['AG'])) {
                $mainData[$key]['main_name'] = $datum['AG'];
            } else {
                $mainData[$key]['main_name'] = '';
            }
            // 联系人
            if(trim($datum['AH'])) {
                $mainData[$key]['main_contacts'] = $datum['AH'];
            } else {
                $mainData[$key]['main_contacts'] = '';
            }
            // 手机号
            if(trim($datum['AI'])) {
                $mainData[$key]['main_tel'] = $datum['AI'];
            } else {
                $mainData[$key]['main_tel'] = '';
            }
            // 省市地址
            if($datum['AJ'] && $datum['AK'] && $datum['AL']) {
                if(!strpos($datum['AJ'], '省')) $datum['AJ'] .= '省';
                if(!strpos($datum['AK'], '市')) $datum['AK'] .= '市';
                $mainData[$key]['main_address'] = [$datum['AJ'],$datum['AK'], $datum['AL'] ];
            } else {
                $mainData[$key]['main_address'] = [];
            }
            // 具体收货地址
            if(trim($datum['AM'])) {
                $mainData[$key]['main_spec_address'] = $datum['AM'];
            } else {
                $mainData[$key]['main_spec_address'] = '';
            }
        }
        self::getCustomers($userCodeArr);
        return [
            'customer' => $insertData,
            'follow' => $followData,
            'main' => $mainData
        ];
    }

    public static function setCustomerLogImportData()
    {
        $base = new Base();
        $data = self::commonImportData(2);
        if(!$data) {
//            return writeJson(201, [], '导入失败');
            throw new Exception('导入失败，内容不能为空');
        }
        $insertData = []; // excel解析后数据
        $userNameArr = []; // 管理员名称数组
        $customerArr = []; // 客户编码数组
        $customerIDArr = [];
        $customerProjectIDArr = [];
        $projectArr = []; // 项目数据


        foreach ($data as $key => $val) {
            array_push($customerArr, $val['D']);
            $customerArr = array_unique($customerArr);

            if(!empty($val['M'])){
                array_push($projectArr, $val['M']);
            }
        }
        $customerIDs = Customer::where('id', 'in', $customerArr)
            ->field(['user_code','id'])
            ->select()
            ->toArray();
        $customerProjectIDs = CustomerProject::where('id', 'in', $projectArr)
            ->field(['customer_id','id'])
            ->select()
            ->toArray();

        if(count($customerIDs) !== count($customerArr)) {
            throw new Exception('存在客户编码错误的行，请及时检查后导入');
        }
        // 项目
        if(count($customerProjectIDs) > 0) {
            foreach ($customerProjectIDs as $datas) {
                $customerProjectIDArr[$datas['id']] = [
                    'id' => $datas['id'],
                    'customer_id' => $datas['customer_id']
                ];
            }
        }
        if(count($customerIDs) > 0) {
            foreach ($customerIDs as $datas) {
                $customerIDArr[$datas['id']] = $datas['id'];
            }
        } else {
            throw new Exception('不存在客户编码，请填入客户编码');
        }
        foreach ($data as $key => $datum) {
            // 项目编码
            $insertData[$key]['user_code'] = $datum['D'];
            // 跟进状态
            if($datum['A']) {
                $insertData[$key]['status'] = $datum['A'];
            } else {
                throw new Exception('客户日志状态不能为空');
            }

            $author = $datum['B'];
            if(empty($author)) {
                throw new Exception('请填写正确的业务员信息');
            } else {
                $insertData[$key]['author'] = $datum['B'];
            }
            // 业务员id
            $searVal = array_search($author, $userNameArr);
            if(!$searVal) {
                $user_id = self::getUserID($author);
                $userNameArr[$user_id] = $author;
            } else {
                $user_id = $searVal;
            }
            $insertData[$key]['user_id'] = $user_id;
            if(isset($customerIDArr[$datum['D']]) && !empty($customerIDArr[$datum['D']])) {
                $insertData[$key]['customer_id'] =  $customerIDArr[$datum['D']];
            }else {
                throw new Exception('客户编码填写有误，请检查');
            };

            if(count($projectArr) !== count($customerProjectIDs)) {
                throw new Exception('存在项目不存在，请及时检查后导入');
            }
            // 判断项目
            if(!empty($datum['M'])) {
                if(isset($customerProjectIDArr[$datum['M']]) && !empty($customerProjectIDArr[$datum['M']])) {
                    $curProjectData = $customerProjectIDArr[$datum['M']];
                    if($curProjectData['customer_id'] == $insertData[$key]['customer_id']) {
                        $insertData[$key]['project_id'] = $curProjectData['id'];
                    } else {
                        throw new Exception('项目和客户不一致，请及时检查后导入');
                    }
                }
            }

            // 咨询日期
            if(!empty($datum['O'])) {
                try{
                    $datum['O'] = DateFormatter::format($datum['O'],'YYYY-m-d');
                } catch (Exception $e) {
                    throw new Exception('沟通时间格式错误');
                }
                $insertData[$key]['create_time'] = $datum['O'];
                $insertData[$key]['update_time'] = $datum['O'];
            }
            $insertData[$key]['content'] = $datum['P'].' | 客户需求：'.$datum['Q'].' | 解决方案：'.$datum['R'].' | 下次沟通内容：'.$datum['S'];
            // 客户需求
            if(empty($datum['Q'])) {
                throw new Exception('客户需求不能为空');
            }
            $insertData[$key]['name'] = $datum['Q'];
        }
        return [
            'log' => $insertData
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
//        $file = iconv("gb2312", "utf-8", $file);
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
                header('Content-type:application/vnd.ms-excel;charset=utf-8;');
                header("Content-Disposition:attachment;filename=$fileName.xls");
                header('Cache-Control: max-age=0');
                header('pragma:public');
                $savePath = 'php://output';
            } else {
                $savePath = $options['savePath'];
            }

            ob_clean();
            ob_start();
            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xls');
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

    public static function getCustomers($ids=[]){
        $result = Customer::withTrashed()->field('id')->select($ids)->toArray();

        if(count($result) > 0) {
            $arr = [];
            foreach ($result as $item) {
                array_push($arr, $item['id']);
            }
            throw new Exception(implode('&',$arr).'编码的客户已存在，请勿重复上传');
        }
    }

}