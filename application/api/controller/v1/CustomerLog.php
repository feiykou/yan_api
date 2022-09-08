<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\CustomerLog as CustomerLogModel;
use app\api\model\Customer as CustomerModel;
use app\api\model\CustomerProject as CustomerProjectModel;
use app\lib\exception\customer_log\CustomerLogException;
use think\Db;
use think\Exception;
use app\api\service\token\LoginToken;
use think\facade\Hook;
use think\facade\Request;

class CustomerLog extends Base
{

    /**
     * 获取详情
     * @param('id','客户日志id','require|number')
     * @url v1/customer_log/id/:id
     * @http GET
     * @param $id
     */
    public function getCustomer($id)
    {
        $result = CustomerLogModel::getDetail($id);
        if (!$result) {
            throw new CustomerLogException([
                'msg' => '客户日志不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }

    /**
     * 获取全部客户日志信息
     * @param customer_id  客户id
     * @auth('获取全部客户日志列表','客户日志管理')
     */
    public function getAllCustomerLogs() {
        $params = Request::get();
        $result = CustomerLogModel::getPaginate(-1,$params);
        return $result;
    }

    /**
     * 通过客户列表进入，则获取全部日志
     * @return array
     */
    public function getCustomerLogsByCustomer() {
        $params = Request::get();
        $result = CustomerLogModel::getPaginate(-1,$params);
        return $result;
    }

    /**
     * 获取当前管理员的客户日志信息
     * @return array
     */
    public function getCustomerLogs(){
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $params = Request::get();
        $result = CustomerLogModel::getPaginate($uid,$params);
        return $result;
    }

    /**
     * 创建信息
     * @validate('CustomerLogForm')
     * @return \think\response\Json
     * @throws CustomerException
     */
    public function create()
    {
        $params = Request::post();
        $token = LoginToken::getInstance();
        if(!array_key_exists('author', $params) || empty($params['author'])) {
            $params['author'] = $token->getCurrentUserName();
            $params['user_id'] = $token->getCurrentUID();
        }
        $result = CustomerLogModel::create($params, true);
        if (!$result) {
            throw new CustomerLogException([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        // 更新跟进时间
        if( isset($params['customer_id'])) {
            CustomerModel::updateFollowTime($params['customer_id']);
        }
        // 根据project_id字段判断，是客户日志还是项目日志
        if(isset($params['project_id'])) {
            CustomerProjectModel::updateCustomerProjectStatus($params['project_id'], $params['status']);
        } else { // 客户状态更新
            if( isset($params['customer_id'])) {
                CustomerModel::updateCustomerStatus($params['customer_id'], $params['status']);
            }
        }
        return writeJson(201, [], '新增成功');
    }

    /**
     * 更新信息
     * @validate('CustomerLogForm.edit')
     * @return \think\response\Json
     */
    public function update()
    {
        $params = Request::put();
        try {
            $result = CustomerLogModel::update($params, [], true);
            CustomerModel::updateCustomerStatus($params['customer_id'], $params['status']);
            Db::commit();
        }catch (Exception $e) {
            Db::rollback();
        }
        if (!$result) {
            throw new CustomerLogException([
                'msg' => '更新失败',
                'error_code' => '51004'
            ]);
        }
        // 更新跟进时间
        if( isset($params['customer_id'])) {
            CustomerModel::updateFollowTime($params['customer_id']);
        }
        // 根据project_id字段判断，是客户日志还是项目日志
        if(isset($params['project_id']) && $params['project_id']) {
            CustomerProjectModel::updateCustomerProjectStatus($params['project_id'], $params['status']);
        } else { // 客户状态更新
            if(isset($params['customer_id']) && $params['customer_id']) {
                CustomerModel::updateCustomerStatus($params['customer_id'], $params['status']);
            }
        }
        return writeJson(201, [], '更新成功');
    }


    /**
     * 删除信息
     * @param('ids','待删除的customer_log_id列表','require|array|min:1')
     * @auth('删除客户日志','客户日志管理')
     * @return \think\response\Json
     */
    public function delete()
    {
        $ids = Request::delete('ids');
        Db::startTrans();
        array_map(function ($id) {
            $customer = CustomerLogModel::get($id);
            if(!$customer) {
                throw new CustomerLogException([
                    'msg' => 'id为' . $id . '不存在'
                ]);
            }
            try{
                $customer->delete();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }

        }, $ids);
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . '客户日志信息');
        return writeJson(201, [], 'CustomerLog删除成功');
    }




}