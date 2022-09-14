<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\CustomerProjectExamine as CustomerProjectExamineModel;
use app\api\service\token\LoginToken;
use app\lib\exception\customer_project\CustomerProjectException;
use think\Db;
use think\Exception;
use think\facade\Request;

class ProjectExamine
{

    /**
     * 获取详情
     * @param('id','客户项目审核id','require|number')
     * @url v1/project_examine/id/:id
     * @http GET
     * @param $id
     */
    public function getProjectExamine($id)
    {
        $result = CustomerProjectExamineModel::getDetail($id);
        if (!$result) {
            throw new CustomerProjectException([
                'msg' => '客户项目审核不存在',
                'error_code' => '51004'
            ]);
        }
        return $result;
    }

    /**
     * 获取全部审核的项目
     * @param('status','审核状态','number')
     * @auth('获取全部审核项目','客户项目管理')
     */
    public function getAllInfo()
    {
        $params = Request::get();
        if(isset($params['status']) && in_array($params['status'], [0,1,2])) {
            $result = CustomerProjectExamineModel::getPaginate('', $params['status']);
        } else {
            $result = CustomerProjectExamineModel::getPaginate();
        }
        return $result;
    }

    /**
     * 获取当前用户提交的审核项目
     * @return array
     */
    public function getCurUserInfos()
    {
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $params = Request::get();
        if(isset($params['status']) && in_array($params['status'], [0,1,2])) {
            $result = CustomerProjectExamineModel::getPaginate($uid, $params['status']);
        } else {
            $result = CustomerProjectExamineModel::getPaginate($uid);
        }
        return $result;
    }

    /**
     * 添加审核项目
     * @param('project_id','项目id','require|number|min:1')
     * @param('customer_id','客户id','require|number|min:1')
     */
    public function create($project_id, $customer_id)
    {
        if(!isset($project_id) || empty($project_id)) {
            throw new CustomerProjectException([
                'msg' => '客户信息不存在',
                'error_code' => '51004'
            ]);
        }
        $customer = \app\api\model\CustomerProject::getCustomerIDByProjectID($project_id);
        if(!$customer) {
            throw new Exception('无法获取到客户id');
        }
        $token = LoginToken::getInstance();
        $uid = $token->getCurrentUid();
        $author = $token->getCurrentUserName();
        $result = CustomerProjectExamineModel::create([
            'project_id'=>$project_id,
            'customer_id' => $customer['id'],
            'link_code' => $customer['link_code'],
            'user_id' => $uid,
            'author' => $author
        ]);
        if(!$result) {
            throw new CustomerProjectException([
                'msg' => '创建失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '新增成功');
    }

    /**
     * @validate('CustomerProjectExamineModelForm.edit')
     * @return \think\response\Json
     * @auth('审核项目','客户项目管理')
     */
    public function update()
    {
        $params = Request::put();
        try {
            $result = CustomerProjectExamineModel::update($params, [], true);
            if($params['status'] == 1) {
                \app\api\model\CustomerProject::update([
                    'id' => $params['project_id'],
                    'follow_status' => config('setting.follow_status_examine')
                ]);
                \app\api\model\Customer::update([
                    'id' => $params['customer_id'],
                    'follow_status' => config('setting.follow_status_examine')
                ]);
            }
            Db::commit();
        }catch (Exception $e) {
            Db::rollback();
            throw new CustomerProjectException([
                'msg' => '更新失败',
                'error_code' => '51004'
            ]);
        }
        if (!$result) {
            throw new CustomerProjectException([
                'msg' => '更新失败',
                'error_code' => '51004'
            ]);
        }
        return writeJson(201, [], '更新成功');
    }
}