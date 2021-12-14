<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\api\model\Spec as SpecModel;
use app\api\model\SpecValue;
use app\lib\exception\spec\SpecException;
use think\facade\Hook;
use think\facade\Request;

class Spec extends Base
{
    /**
     * 获取全部规格
     * @return mixed
     * @throws SpecException
     */
    public function getSpecs()
    {
        $result = SpecModel::getSpecPaginate();
        if(!$result) {
            throw new SpecException([
                'error_code' => '50001',
                'msg' => '规格为空'
            ]);
        }
        return $result;
    }

    /**
     * 获取单个规格
     * @param('id','规格','require|number')
     * @param $id
     * @return mixed
     * @throws SpecException
     */
    public function getSpec($id)
    {
        $result = SpecModel::getSpec($id);
        if(!$result){
            throw new SpecException([
                'msg' => '规格不存在',
                'error_code' => '50001'
            ]);
        }
        return $result;
    }

    /**
     * 新建规格
     * @validate('SpecForm')
     * @return \think\response\Json
     */
    public function create()
    {
        $params = Request::post();
        $result = SpecModel::create($params, true);
        if (!$result) {
            throw new SpecException([
                'msg' => '创建规格失败',
                'error_code' => '50002'
            ]);
        }
        return writeJson(201, [], '新增规格成功');
    }

    /**
     * 更新规格
     * @validate('SpecForm.edit')
     * @return \think\response\Json
     */
    public function update()
    {
        $params = Request::put();
        $SpecModel = new SpecModel();
        $result = $SpecModel->save($params, ['id' => $params['id']]);
        if(!$result) {
            throw new SpecException([
                'msg' => '更新规格失败',
                'error_code' => '50003'
            ]);
        }
        return writeJson(201, [], '更新规格成功');
    }

    /**
     * 删除规格
     * @param('ids','待删除的规格id列表','require|array|min:1')
     * @auth('删除规格','规格管理')
     * @return \think\response\Json
     */
    public function delSpec()
    {
        $ids = Request::delete('ids');
        array_map(function ($id) {
            $spec = SpecModel::get($id, 'items');
            if(!$spec) {
                throw new SpecException([
                    'msg' => 'id为' . $id . '规格不存在'
                ]);
            }
            $spec->together('items')->delete();
        }, $ids);
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . '规格');
        return writeJson(201, [], '规格删除成功');
    }


    /**
     * 获取规格值
     * @param('id','规格值','require|number')
     * @param $id
     * @return mixed
     * @throws SpecException
     */
    public function getItem($id)
    {
        $result = SpecValue::get($id);
        if(!$result) {
            throw new SpecException([
                'error_code' => '50005',
                'msg' => '规格值不存在'
            ]);
        }
        return $result;
    }


    /**
     * 新建规格值
     * @validate('SpecValueForm')
     * @return \think\response\Json
     */
    public function itemCreate()
    {
        $params = Request::post();
        $result = SpecValue::create($params, true);
        if (!$result) {
            throw new SpecException([
                'msg' => '创建规格值失败',
                'error_code' => '50006'
            ]);
        }
        return writeJson(201, [], '新增规格值成功');
    }

    /**
     * 更新规格值
     * @validate('SpecValueForm.edit')
     * @return \think\response\Json
     */
    public function itemUpdate()
    {
        $params = Request::put();
        $SpecValueModel = new SpecValue();
        $result = $SpecValueModel->save($params, ['id' => $params['id']]);
        if(!$result) {
            throw new SpecException([
                'msg' => '更新规格值失败',
                'error_code' => '50007'
            ]);
        }
        return writeJson(201, [], '更新规格值成功');
    }

    /**
     * 删除规格值信息
     * @param('ids','待删除的规格值id列表','require|array|min:1')
     * @auth('删除规格值','规格管理')
     * @return \think\response\Json
     */
    public function delItem()
    {
        $ids = Request::delete('ids');
        array_map(function ($id) {
            $banner = SpecValue::get($id);
            if(!$banner) {
                throw new SpecException([
                    'msg' => 'id为' . $id . '规格值不存在'
                ]);
            }
            $banner->delete();
        }, $ids);
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . '的规格值');
        return writeJson(201, [], '规格值删除成功');
    }
}