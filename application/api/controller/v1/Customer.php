<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\Customer as CustomerModel;
use app\api\model\CustomerAdd;
use app\api\model\CustomerMain;
use app\lib\exception\customer\CustomerException;
use app\lib\token\Token;
use LinCmsTp5\exception\BaseException;
use think\Db;
use think\Exception;
use app\api\service\token\LoginToken;
use think\facade\Hook;
use think\facade\Request;

class Customer extends Base
{

    /**
     * 获取全部客户信息
     * @validate('CustomerFilter')
     * @auth('获取全部客户信息','客户管理')
     */
    public function getAllCustomer() {
        $params = Request::get();
        // type==1  3天未跟进数据
        if(isset($params['type']) && $params['type'] == 1){
            $result = CustomerModel::passNoFollowData(-1, $params);
        }else {
            $result = CustomerModel::getCustomerPaginate(-1,$params);
        }
        return $result;
    }

    /**
     * 获取全部Customer信息
     * @return array
     * @validate('CustomerFilter')
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public function getCustomers()
    {
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $params = Request::get();
        // type==1  3天未跟进数据
        if(isset($params['type']) && $params['type'] == 1){
            $result = CustomerModel::passNoFollowData($uid, $params);
        }else {
            $result = CustomerModel::getCustomerPaginate($uid,$params);
        }
        return $result;
    }

    /**
     * 获取公域池客户
     * @return array
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public function getPublicCustomers()
    {
        $result = CustomerModel::getCustomerPaginate(0);
        return $result;
    }

    /**
     * 获取Customer详情
     * @param('id','customer的id','require|number')
     * @url v1/customer/id/:id
     * @http GET
     * @param $id
     */
    public function getCustomer($id)
    {
        $result = CustomerModel::getCustomerDetail($id);
        if (!$result) {
            throw new CustomerException([
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
            throw new CustomerException([
                'msg' => '客户信息不存在',
                'error_code' => '51004'
            ]);
        }
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
        // 检测公司客户是否超过20个
        $this->isAddCustomer();
        $params = Request::post();
        $params = $this->setAuthor($params);
        $token = LoginToken::getInstance();
        $params['original_user_id'] = $token->getCurrentUid();
        $code = json_decode($this->makeCustomerCode()->getContent(),true);
        $params['user_code'] = $code['code'];
        $params['link_code'] = $this->makeLinkIndex();
        // 初始化跟进时间
//        $params['follow_time'] = (new CustomerModel())->formatDateTime('Y-m-d H:i:s.u');
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
        $params = $this->setAuthor($params);
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
     * 获取超过3天未跟进客户
     */
    public function getPassFollowData()
    {
//        passNoFollowData
    }

    /**
     * 创建，更新跟进信息
     * @return \think\response\Json
     * @throws CustomerException
     */
    public function followUpdate()
    {
        $params = Request::put();
        if(array_key_exists('id', $params)) {
            $result = (new CustomerAdd())->save($params, ['id' => $params['id']]);
        } else {
            $result = (new CustomerAdd())->save($params, []);
        }

        if (!$result) {
            throw new CustomerException([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '更新成功');
    }

    /**
     * 创建，更新主客户信息
     * @return \think\response\Json
     * @throws CustomerException
     */
    public function MainUpdate()
    {
        $params = Request::put();
        if(array_key_exists('id', $params)) {
            $result = (new CustomerMain())->save($params, ['id' => $params['id']]);
        } else {
            $result = (new CustomerMain())->save($params, []);
        }
        if (!$result) {
            throw new CustomerException([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '更新成功');
    }

    /**
     * 公域池抢单设置信息
     * @param('cutomer_id','customer的id','require|number')
     * @url v1/customer/common_set/:cutomer_id
     * @http GET
     * @param $id
     */
    public function setGetCommonCustomer($cutomer_id)
    {
        $params = $this->setAuthor();
        // 释放客户回归正常状态
        $params['is_release_user'] = 0;
        $result = CustomerModel::where(['id'=>$cutomer_id])->update($params);
        if (!$result) {
            throw new CustomerException([
                'msg' => '获取失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '获取成功');
    }

    /**
     * 释放客户进去公域池（支持多选）
     * @param('ids','待释放的customer_id列表','require|array|min:1')
     */
    public function releaseCustomers()
    {
        $ids = Request::put('ids');
        $token = LoginToken::getInstance();
        $arr = [];
        foreach ($ids as $id) {
            $arr[] = [
                'id' => $id,
                'old_author' => $token->getCurrentUserName(),
                'old_user_id' => $token->getCurrentUID(),
                'author' => '',
                'user_id' => 0
            ];
        }
        if(count($arr) > 0) {
            $result = (new CustomerModel)->isUpdate()->saveAll($arr);
            if (!$result) {
                throw new CustomerException([
                    'msg' => '释放进公域池失败',
                    'error_code' => '51004'
                ]);
            }
            return writeJson(201, [], '释放进公域池成功');
        }
        throw new CustomerException([
            'msg' => '请先选择客户',
            'error_code' => '51004'
        ]);
    }

    // 设置进入公域池
    private function setAuthor($params=[])
    {
        $token = LoginToken::getInstance();
        // 主动指定把客户分配给谁
        if(isset($params['dicider_user']) && !empty($params['dicider_user'])) {
            if($token->getCurrentUID() == $params['user_id']) {
                $params['old_author'] = $token->getCurrentUserName();
                $params['old_user_id'] = $token->getCurrentUID();
                $params['author'] = $params['dicider_user']['name'];
                $params['user_id'] = $params['dicider_user']['id'];
            }
            unset($params['dicider_user']);
            return $params;
        }
        // 释放客户
        if(array_key_exists('is_release_user', $params) && $params['is_release_user'] == 1){
            $params['old_author'] = $token->getCurrentUserName();
            $params['old_user_id'] = $token->getCurrentUID();
            $params['author'] = '';
            $params['user_id'] = 0;
        } else {
            // 设置当前写入客户的id
            $params['author'] = $token->getCurrentUserName();
            $params['user_id'] = $token->getCurrentUID();
        }
        return $params;
    }

    /**
     * 公司开发客户上限
     * @throws CustomerException
     */
    private function isAddCustomer()
    {
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $num = CustomerModel::where('original_user_id','neq', $uid)->getNumRows();
        if($num >= 20) {
            throw new CustomerException([
                'msg' => '公司开发的已超过上限，请释放公司客户',
                'error_code' => '51004'
            ]);
        }
    }
}