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
            var_dump($e);
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