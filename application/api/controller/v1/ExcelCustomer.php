<?php


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\api\model\CustomerProject;
use app\api\model\CustomerMain;
use app\api\model\Customer;
use app\lib\exception\excel_customer\ExcelCustomerException;
use LinCmsTp5\exception\BaseException;
use think\Db;
use think\Exception;
use app\api\service\ExcelCustomer as ExcelCustomerService;
use app\api\model\ExcelCustomer as ExcelCustomerModel;
use think\facade\Request;

class ExcelCustomer extends Base
{
    /**
     * 导入客户
     * @return \think\response\Json
     * @throws BaseException
     */
    public function importCustomer()
    {
        try{
            $result = ExcelCustomerService::setImportData();
        } catch (Exception $e){
            throw new ExcelCustomerException([
                'msg' => $e->getMessage()
            ]);
        }
        Db::startTrans();
        try{
            $c1 = (new Customer())->insertAll($result['customer']);
            $c2 = (new CustomerProject())->insertAll($result['follow']);
            $c3 = (new CustomerMain())->insertAll($result['main']);
            if(!$c1 || !$c2 || !$c3) {
                Db::rollback();
            }
            Db::commit();
            return writeJson(201, [], '导入成功');
        } catch (Exception $e){
            Db::rollback();
            var_dump($e);
        }
        return writeJson(201, [], '导入失败');
    }

    /**
     * 导出excel
     * @param('ids','客户id','require')
     * @return \think\response\Json
     */
    public function exportCustomer($ids)
    {
        $ids = Request::get('ids');
        try {
            if(empty($ids))  throw new Exception('参数必须填写');
            $ids = explode(',', $ids);
            $mark = true;
            foreach ($ids as $num) {
                if(!is_numeric($num)) {
                    $mark = false;
                }
            }
            if(!$mark) throw new Exception('参数必须是数字');
        } catch (Exception $e) {
            throw new ExcelCustomerException([
                'msg' => $e->getMessage()
            ]);
        }

        $cellName=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH'];
        $headData = ['跟进状态','接入日期','','客户编号','客户来源','省份','城市','联系人','联系方式','客户类型','客户名称','客户咨询','跟进次数','原因','使用场景','行业','产品类型','产品规格','数量','报价','需求分析（客户背景及痛点）','提供对应解决方案','工程安装解决方案','客户关注产品亮点','客户价值','业务跟进困难点','客户反馈','客户名称（业主方）','联系人（业主方）','电话（业主方）','省份（业主方）','城市（业主方）','区（业主方）','具体收货地址'];
        $sheetHeader = []; // 表头
        $setBgCell = []; // 设置指定单元格背景颜色
        $cellWidth = [];
        foreach ($cellName as $key => $item) {
            // 设置表头文字
            $sheetHeader[$item.'1'] = $headData[$key];
            array_push($setBgCell,$item.'1');
            // 设置默认行宽
            $cellWidth[$item] = 18;
        }
        $cellWidth = array_merge($cellWidth,[
            'D' => 20, 'k' => 28, 'L' => 55, 'U' => 40, 'V' => 40, 'Z' => 40
        ]);
        // 导出数据
        $data = Customer::getCustomerAndProject($ids)->toArray();
        $excelFormatData = ExcelCustomerModel::handleExportData($data);
        array_unshift($excelFormatData, $sheetHeader);
        $result = ExcelCustomerService::exportExcel($excelFormatData,'测试', [
            'setARGB' => $setBgCell,
            'setBorder' => true,
            'setWidth' => $cellWidth
        ]);
        if(!$result) {
            return writeJson(201, [], '导入失败');
        }
        return writeJson(201, [], '导入成功');
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

    /**
     * 导出客户
     */


}