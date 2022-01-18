<?php


namespace app\api\service;


use app\api\controller\Base;
use app\api\service\token\LoginToken;
use LinCmsTp5\admin\model\LinUser;
use LinCmsTp5\exception\BaseException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            $insertData[$key]['create_time'] = date('Y-m-d',strtotime($datum['B']));
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

    public static function getUserID($username="")
    {
        $id = LinUser::where('username',$username)->value('id');
        if(!$id) {
            throw new Exception('业务员不存在，请先创建业务员信息');
        }
        return $id;
    }
}