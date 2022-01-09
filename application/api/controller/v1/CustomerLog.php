<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\CustomerLog as CustomerLogModel;
use app\api\model\Customer as CustomerModel;
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
     * @param user_id  客户id
     * @auth('获取全部客户日志信息','客户日志管理')
     */
    public function getAllCustomer() {
        $params = Request::get();
        $result = CustomerLogModel::getPaginate($params['user_id']);
        return $result;
    }

    /**
     * 创建信息
     * @validate('TypeForm')
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
        if( isset($params['customer_id']) && $params['customer_id']) {
            CustomerModel::updateFollowTime($params['customer_id']);
        }
        return writeJson(201, [], '新增成功');
    }

    /**
     * 更新信息
     * @validate('TypeForm.edit')
     * @return \think\response\Json
     */
    public function update()
    {
        $params = Request::put();
        $result = CustomerLogModel::update($params, [], true);
        if (!$result) {
            throw new CustomerLogException([
                'msg' => '更新失败',
                'error_code' => '51004'
            ]);
        }
        // 更新跟进时间
        if( isset($params['customer_id']) && $params['customer_id']) {
            $followResult = CustomerModel::updateFollowTime($params['customer_id']);
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