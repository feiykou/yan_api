<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\Customer as CustomerModel;
use app\lib\exception\customer\CustomerException;
use app\lib\token\Token;
use think\Db;
use think\Exception;
use app\api\service\token\LoginToken;
use think\facade\Hook;
use think\facade\Request;

class Customer extends Base
{

    /**
     * 获取Customer详情
     * @auth('获取客户审核权限','客户管理')
     * @param('id','customer的id','require|number')
     * @url v1/customer/id/:id
     * @http GET
     * @param $id
     */
    public function getCustomer($id)
    {
        $result = CustomerModel::getCustomerDetail($id);
        if (!$result) {
            throw new ColumnException([
                'msg' => '客户信息不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }

    /**
     * 获取Customer详情
     * @param('id','customer的id','require|number')
     * @url v1/customer/id/:id
     * @http GET
     * @param $id
     */
    public function getStatusCustomer($id)
    {
        $result = CustomerModel::getCustomerDetail($id, 'status');
        if (!$result) {
            throw new ColumnException([
                'msg' => '客户信息不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }


    /**
     * 获取全部客户信息
     * @auth('获取全部客户信息','客户管理')
     */
    public function getAllCustomer() {
        $result = CustomerModel::getColumnPaginate();
        return $result;
    }

    /**
     * 获取全部Customer信息
     * @return array
     * @auth('获取客户信息','客户管理')
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public function getCustomers()
    {
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $result = CustomerModel::getColumnPaginate($uid);
        return $result;
    }

    /**
     * 创建Customer信息
     * @validate('CustomerForm')
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
        $code = json_decode($this->makeCustomerCode()->getContent(),true);
        $params['user_code'] = $code['code'];
        $result = CustomerModel::create($params, true);
        if (!$result) {
            throw new CustomerException([
                'msg' => '创建Customer失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '新增Customer成功');
    }

    /**
     * 更新Customer信息
     * @validate('CustomerForm.edit')
     * @return \think\response\Json
     * @throws CustomerException
     */
    public function update()
    {
        $params = Request::put();
        $result = CustomerModel::update($params, [], true);
        if (!$result) {
            throw new CustomerException([
                'msg' => '创建Customer失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '更新Customer成功');
    }


    /**
     * 删除Customer信息
     * @param('ids','待删除的customer_id列表','require|array|min:1')
     * @auth('删除客户','客户管理')
     * @return \think\response\Json
     */
    public function delete()
    {
        $ids = Request::delete('ids');
        Db::startTrans();
        array_map(function ($id) {
            $customer = CustomerModel::get($id);
            if(!$customer) {
                throw new CustomerException([
                    'msg' => 'id为' . $id . 'Customer不存在'
                ]);
            }
            try{
                $customer->delete();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }

        }, $ids);
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . 'Customer信息');
        return writeJson(201, [], 'Customer删除成功');
    }


    /**
     * 生成客户编码
     * @return string
     */
    private function makeCustomerCode()
    {
        $codeSn =
            str_replace('20','',intval(date('Y'))) . date('m') . sprintf(
                '%02d', rand(1000, 9999));
        return json([
            'code' => $codeSn
        ]);
    }
}