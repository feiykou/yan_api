<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\CustomerReport as Modelv1;
use app\lib\exception\customer_report\CustomerReportException as ExceptionV1;
use think\Db;
use think\Exception;
use app\api\service\token\LoginToken;
use think\facade\Hook;
use think\facade\Request;

class CustomerReport extends Base
{

    /**
     * 获取详情，有审核权限
     * @auth('获取客户报表审核权限','客户报表管理')
     * @param('id','customer的id','require|number')
     * @url v1/customer/id/:id
     * @http GET
     * @param $id
     */
    public function getDetail($id)
    {
        $result = Modelv1::getDetail($id);
        if (!$result) {
            throw new ExceptionV1([
                'msg' => '客户信息不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }

    /**
     * 获取详情，无审核权限
     * @param('id','customer的id','require|number')
     * @url v1/customer/id/:id
     * @http GET
     * @param $id
     */
    public function getStatusDetail($id)
    {
        $result = Modelv1::getDetail($id, 'status');
        if (!$result) {
            throw new ExceptionV1([
                'msg' => '客户报表信息不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }


    /**
     * 获取全部客户报表信息
     * @auth('获取全部客户报表信息','客户报表管理')
     */
    public function getAll() {
        $result = Modelv1::getColumnPaginate();
        return $result;
    }

    /**
     * 获取全部Customer报表信息，只能获取当前管理员信息
     * @return array
     * @auth('获取客户报表信息','客户报表管理')
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public function getlists()
    {
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $result = Modelv1::getColumnPaginate($uid);
        return $result;
    }

    /**
     * 创建Customer报表
     * @validate('CustomerReportForm')
     * @return \think\response\Json
     * @throws ExceptionV1
     */
    public function create()
    {
        $params = Request::post();
        $result = Modelv1::create($params, true);
        if (!$result) {
            throw new ExceptionV1([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '新增成功');
    }

    /**
     * 更新Customer报表信息
     * @validate('CustomerReportForm.edit')
     * @return \think\response\Json
     * @throws ExceptionV1
     */
    public function update()
    {
        $params = Request::put();
        $result = Modelv1::update($params, [], true);
        if (!$result) {
            throw new ExceptionV1([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '更新成功');
    }


    /**
     * 删除Customer报表信息
     * @param('ids','待删除的id','require|array|min:1')
     * @auth('删除客户报表','客户报表管理')
     * @return \think\response\Json
     */
    public function delete()
    {
        $ids = Request::delete('ids');
        Db::startTrans();
        array_map(function ($id) {
            $customer = Modelv1::get($id);
            if(!$customer) {
                throw new ExceptionV1([
                    'msg' => 'id为' . $id . '信息不存在'
                ]);
            }
            try{
                $customer->delete();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }

        }, $ids);
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . 'CustomerReport信息');
        return writeJson(201, [], 'CustomerReport删除成功');
    }

}