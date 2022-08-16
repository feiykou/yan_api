<?php


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\api\model\CustomerProject;
use app\api\model\CustomerMain;
use app\api\model\Customer;
use app\api\model\CustomerLog;
use app\lib\exception\excel_customer\ExcelCustomerException;
use LinCmsTp5\exception\BaseException;
use think\Db;
use think\Exception;
use app\api\service\ExcelCustomer as ExcelCustomerService;
use app\api\model\ExcelCustomer as ExcelCustomerModel;
use think\facade\Request;
use think\Model;

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
            var_dump($e);
            Db::rollback();
        }
        var_dump(111);
        return writeJson(201, [], '导入失败');
    }

    /**
     * 导出excel
     * @return \think\response\Json
     */
    public function exportCustomer()
    {
//        $ids = Request::get('ids');
//        try {
//            if(empty($ids))  throw new Exception('参数必须填写');
//            $ids = explode(',', $ids);
//            $mark = true;
//            foreach ($ids as $num) {
//                if(!is_numeric($num)) {
//                    $mark = false;
//                }
//            }
//            if(!$mark) throw new Exception('参数必须是数字');
//        } catch (Exception $e) {
//            throw new ExcelCustomerException([
//                'msg' => $e->getMessage()
//            ]);
//        }
        $params = Request::get('params');
        if(!isset($params) || !$params) {
            $params = [];
        } else {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        // 导出数据
        $data = Customer::getCustomerAndProject($params)->toArray();
        $result = $this->exportCustomerExcelInfo($data);
        if(!$result) {
            return writeJson(201, [], '导入失败');
        }
        return writeJson(201, [], '导入成功');
    }

    /**
     * 导出项目excel
     * @param('ids','项目id','require')
     * @return \think\response\Json
     */
    public function exportCustomerProject($ids)
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
        // 导出数据
        $data = CustomerProject::getProjectAndCustomer($ids)->toArray();
        $handleData = [];
        foreach ($data as $val) {
            if(!$val['customer']) continue;
            $newData = [];
            $address = $val['customer']['address'];
            // 把地址对象变成数组
            if($address) {
                $val['customer']['address'] = json_decode(json_encode($address), true);
            }
            if($val['customer_main']) {
                $main_address = $val['customer_main']['main_address'];
                $val['customer_main']['main_address'] = json_decode(json_encode($main_address), true);
            }
            $newData = $val['customer'];
            $newData['customer_main'] = $val['customer_main'];
            unset($val['customer']);
            unset($val['customer_main']);
            $newData['customer_project'] = [$val];
            array_push($handleData, $newData);
        }
        $result = $this->exportCustomerExcelInfo($handleData);
        if(!$result) {
            return writeJson(201, [], '导入失败');
        }
        return writeJson(201, [], '导入成功');
    }

    private function exportCustomerExcelInfo($data = [])
    {
        $cellName=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM'];
        $headData = ['跟进状态','接入日期','业务员','客户编号','客户来源','省份','城市','联系人','联系方式','客户类型','客户名称','项目名','项目状态','订单编码','成交时间','客户咨询','跟进次数','原因','使用场景','行业','产品类型','产品规格','数量','报价','需求分析（客户背景及痛点）','提供对应解决方案','工程安装解决方案','客户关注产品亮点','客户价值','业务跟进困难点','客户反馈','项目来源','订单编号','成交时间','客户名称（业主方）','联系人（业主方）','电话（业主方）','省份（业主方）','城市（业主方）','区（业主方）','具体收货地址'];
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
        $excelFormatData = ExcelCustomerModel::handleExportData($data);
        array_unshift($excelFormatData, $sheetHeader);
        $result = ExcelCustomerService::exportExcel($excelFormatData,'客户信息模板', [
            'setARGB' => $setBgCell,
            'setBorder' => true,
            'setWidth' => $cellWidth
        ]);
        return $result;
    }

    /**
     * 导入客户日志
     * @return \think\response\Json
     * @throws BaseException
     */
    public function importCustomerLog()
    {
        try{
            $result = ExcelCustomerService::setCustomerLogImportData();
        } catch (Exception $e){
            throw new ExcelCustomerException([
                'msg' => $e->getMessage()
            ]);
        }
        Db::startTrans();
        try{
            $c1 = (new CustomerLog())->insertAll($result['log']);
            if(!$c1) {
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
     * 导出客户日志excel
     * @return \think\response\Json
     */
    public function exportCustomerLog()
    {
//        $ids = Request::get('ids');
//        try {
//            if(empty($ids))  throw new Exception('参数必须填写');
//            $ids = explode(',', $ids);
//            $mark = true;
//            foreach ($ids as $num) {
//                if(!is_numeric($num)) {
//                    $mark = false;
//                }
//            }
//            if(!$mark) throw new Exception('参数必须是数字');
//        } catch (Exception $e) {
//            throw new ExcelCustomerException([
//                'msg' => $e->getMessage()
//            ]);
//        }

        $cellName=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q'];
        $headData = ['状态','业务','来源','客户编号','客户名称','省市','市','区','联系人','电话','客户类型','客户行业','项目id','项目名','沟通时间','沟通内容','客户需求'];
        $sheetHeader = []; // 表头
        $setBgCell = []; // 设置指定单元格背景颜色
        $cellWidth = [];
        foreach ($cellName as $key => $item) {
            // 设置表头文字
            $sheetHeader[$item.'1'] = $headData[$key];
            array_push($setBgCell,$item.'1');
            // 设置默认行宽
            $cellWidth[$item] = 12;
        }
        $cellWidth = array_merge($cellWidth,[
            'J' => 20, 'L' => 20, 'N' => 20, 'O' => 40, 'P' => 40
        ]);
        // 导出数据
        $params = Request::get('params');
        if(!isset($params) || !$params) {
            $params = [];
        } else {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        $data = CustomerLog::getCustomerLogAndCustomer($params)->toArray();
        $excelFormatData = ExcelCustomerModel::handleLogExportData($data);
        array_unshift($excelFormatData, $sheetHeader);
        $result = ExcelCustomerService::exportExcel($excelFormatData,'客户日志模板', [
            'setARGB' => $setBgCell,
            'setBorder' => true,
            'setWidth' => $cellWidth
        ]);
        if(!$result) {
            return writeJson(201, [], '导入失败');
        }
        return writeJson(201, [], '导入成功');
    }



}