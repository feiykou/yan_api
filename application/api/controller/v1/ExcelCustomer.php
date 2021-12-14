<?php


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\api\model\CustomerAdd;
use app\api\model\CustomerMain;
use app\api\model\Customer;
use app\api\service\token\LoginToken;
use LinCmsTp5\exception\BaseException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Db;
use think\Exception;
use think\facade\Env;

class ExcelCustomer extends Base
{
    /**
     * 导入客户日志
     * @return \think\response\Json
     * @throws BaseException
     */
    public function importCustomer()
    {
        $data = $this->commonImportData(3);
        if(!$data) {
            return writeJson(201, [], '导入失败');
        }
        $insertData = [];
        $followData = [];
        $mainData = [];
        foreach ($data as $key => $datum) {
            $linkIndex = $this->makeLinkIndex();
            $insertData[$key]['create_time'] = date('Y-m-d',strtotime($datum['B']));
            $insertData[$key]['channel'] = $datum['E'];
            $code = json_decode($this->makeCustomerCode()->getContent(),true);
            $token = LoginToken::getInstance();
            $insertData[$key]['follow_status'] = $datum['A'];
            $insertData[$key]['author'] = $token->getCurrentUserName();
            $insertData[$key]['user_code'] = $code['code'];
            $insertData[$key]['user_id'] = $token->getCurrentUID();
            $insertData[$key]['link_code'] = $linkIndex;
            if($datum['F'] && $datum['G']) {
                $insertData[$key]['address'] = [$datum['F'],$datum['G']];
            }
//            $insertData[$key]['address'] = json([$datum['F'],$datum['G']]);
            $insertData[$key]['contacts_name'] = $datum['H'];
            $insertData[$key]['telephone'] = $datum['I'];
            $insertData[$key]['name'] = $datum['K'];
            $insertData[$key]['customer_type'] = $datum['J'];
//            $insertData[$key]['purpose'] = $datum['E'];

            $followData[$key]['link_code'] = $linkIndex;
            $followData[$key]['user_id'] = 0;
            $followData[$key]['scene'] = $datum['M'];
            $followData[$key]['industry'] = $datum['N'];
            $followData[$key]['demand_bg'] = $datum['S'];
            $followData[$key]['demand_desc'] = $datum['L'];
            $followData[$key]['solution'] = $datum['T'];
            $followData[$key]['install_solution'] = $datum['U'];
            $followData[$key]['product_lights'] = $datum['V'];
            $followData[$key]['custom_value'] = $datum['W'];
            $followData[$key]['follow_difficulty'] = $datum['X'];
            $followData[$key]['custom_feedback'] = $datum['Y'];

            $mainData[$key]['link_code'] = $linkIndex;
            $mainData[$key]['user_id'] = 0;
            $mainData[$key]['main_name'] = $datum['Z'];
            $mainData[$key]['main_contacts'] = $datum['AA'];
            $mainData[$key]['main_tel'] = $datum['AB'];
            if($datum['AE'] && $datum['AC'] && $datum['AD']) {
                $mainData[$key]['main_address'] = [$datum['AC'],$datum['AD'], $datum['AE'] ];
            }
            $mainData[$key]['main_spec_address'] = $datum['AF'];

        }
//        var_dump($followData);
//        var_dump($mainData);
        Db::startTrans();
        try{
            (new Customer())->insertAll($insertData);
            (new CustomerAdd())->insertAll($followData);
            (new CustomerMain())->insertAll($mainData);
            Db::commit();
            return writeJson(201, [], '导入成功');
        } catch (Exception $e){
            var_dump($e);
            Db::rollback();
        }
        return writeJson(201, [], '导入失败');
    }

    /**
     * 导入客户日志
     * @return \think\response\Json
     * @throws BaseException
     */
    public function importCustomerLog()
    {
        $data = $this->commonImportData(2);
        if(!$data) {
            return writeJson(201, [], '导入失败');
        }
        $insertData = [];
        foreach ($data as $key => $datum) {
            $insertData[$key]['name'] = $datum['A'];
            $insertData[$key]['content'] = $datum['B'];
            $insertData[$key]['author'] = $datum['C'];
            $insertData[$key]['user_id'] = $datum['D'];
            $insertData[$key]['customer_id'] = $datum['E'];
            // 时间是用户添加还是自己添加
//                $insertData[$key]['create_time'] = ($datum['E'] - 25569) * 24 * 3600;
        }
        $success_count = (new \app\api\model\Customer())->insertAll($insertData);
        if(!$success_count) {
            return writeJson(201, [], '导入失败');
        }
        return writeJson(201, [], '导入成功');
    }

    public function commonImportData($start_row = 1)
    {
        $excel = new ExcelCustomer();
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
            $data = $excel->importExecl($file_name, $start_row);
            return $data;
        } else {
            return false;
        }
    }

    public function importExecl($file = '', $start_row = 1)
    {
        $file = iconv("utf-8", "gb2312", $file);
        if (empty($file) OR !file_exists($file)) {
            throw new BaseException([
                'msg' => '文件不存在',
                'error_code' => '51004'
            ]);
        }
//      创建 Xlsx $objRead
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
                $cell = $currSheet->getCell($cellId);

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

}