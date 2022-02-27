<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\CustomerProject as CustomerProjectModel;
use app\lib\exception\customer_project\ProjectException;
use think\Db;
use think\Exception;
use app\api\service\token\LoginToken;
use think\facade\Hook;
use think\facade\Request;
/**
 * 功能实现：
 * 1、获取全部的项目（权限：super）
 * 2、获取管理员的所有项目
 * 3、获取当前客户的项目
 * 4、更新项目
 * 5、创建项目
 * 6、删除项目（权限设置）
 * 7、获取项目
 */
class CustomerProject extends Base
{
    /**
     * 获取详情
     * @param('id','客户项目id','require|number')
     * @url v1/customer_project/id/:id
     * @http GET
     * @param $id
     */
    public function getCustomerProject($id)
    {
        $result = CustomerProjectModel::getDetail($id);
        if (!$result) {
            throw new ProjectException([
                'msg' => '客户项目不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }

    /**
     * 获取全部客户项目信息
     * @validate('CustomerProjectFilter')
     * @auth('全部项目信息','客户项目管理')
     */
    public function getAllCustomerProjects()
    {
        $params = Request::get();
        $result = CustomerProjectModel::getPaginate($params);
        return $result;
    }

    /**
     * 获取当前管理员的
     * @param('customer_id','管理员id','number')
     * @validate('CustomerProjectFilter')
     * @param $customer_id 指的是link_code
     * @param bool $isAdmin  0: 没有管理员的条件   1：查询管理员
     */
    public function getCustomerProjects($customer_id, $isAdmin=1)
    {
        $UID = '';
        $isAdmin = $isAdmin == 1;
        if($isAdmin) {
            $token = LoginToken::getInstance();
            $UID = $token->getCurrentUID();
        }
        $params = Request::get();
        $result = CustomerProjectModel::getPaginate($UID, $customer_id, $params);
        return $result;
    }

    /**
     * 创建信息
     * @validate('CustomerProjectForm')
     * @return \think\response\Json
     * @throws ProjectException
     */
    public function create()
    {
        $params = Request::post();
        $token = LoginToken::getInstance();
        if(!array_key_exists('author', $params) || empty($params['author'])) {
            $params['author'] = $token->getCurrentUserName();
            $params['user_id'] = $token->getCurrentUID();
        }
        $result = CustomerProjectModel::create($params, true);
        if (!$result) {
            throw new ProjectException([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '新增成功');
    }

    /**
     * 更新信息
     * @validate('CustomerProjectForm.edit')
     * @return \think\response\Json
     */
    public function update()
    {
        $params = Request::put();
        $result = CustomerProjectModel::update($params, [], true);
        if (!$result) {
            throw new ProjectException([
                'msg' => '更新失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '更新成功');
    }


    /**
     * 删除信息
     * @param('ids','待删除的customer_project_id列表','require|array|min:1')
     * @auth('删除客户项目','客户项目管理')
     * @return \think\response\Json
     */
    public function delete()
    {
        $ids = Request::delete('ids');
        Db::startTrans();
        array_map(function ($id) {
            $customer = CustomerProjectModel::get($id);
            if(!$customer) {
                throw new ProjectException([
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
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . '客户项目信息');
        return writeJson(201, [], '客户项目删除成功');
    }




}