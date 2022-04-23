<?php


namespace app\api\controller\v1;

use app\api\controller\Base;
use app\api\model\Customer as CustomerModel;
use app\api\model\Type as TypeModel;
use app\lib\exception\Type\TypeException;
use LinCmsTp5\exception\BaseException;
use think\Db;
use think\Exception;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Hook;
use think\facade\Request;

class Type extends Base
{

    /**
     * 获取全部类型
     * @return mixed
     * @throws TypeException
     */
    public function getTypes()
    {
        $result = TypeModel::getTypePaginate();
        if(!$result) {
            throw new TypeException([
                'error_code' => '50001',
                'msg' => '类型为空'
            ]);
        }
        return $result;
    }

    /**
     * 获取单个类型
     * @param('id','类型','require|number')
     * @param $id
     * @return mixed
     * @throws TypeException
     */
    public function getType($id)
    {
        $result = TypeModel::get($id);
        if(!$result){
            throw new TypeException([
                'msg' => '类型不存在',
                'error_code' => '50001'
            ]);
        }
        return $result;
    }


    /**
     * 新建类型
     * @validate('TypeForm')
     * @return \think\response\Json
     */
    public function create()
    {
        $params = Request::post();
        $result = TypeModel::create($params, true);
        if (!$result) {
            throw new TypeException([
                'msg' => '创建类型失败',
                'error_code' => '50002'
            ]);
        }
        self::setTypeCache($params['field'], $params);
        return writeJson(201, [], '新增类型成功');
    }

    /**
     * 更新类型
     * @validate('TypeForm.edit')
     * @return \think\response\Json
     */
    public function update()
    {
        $params = Request::put();
        $TypeModel = new TypeModel();
        $result = $TypeModel->save($params, ['id' => $params['id']]);
        if(!$result) {
            throw new TypeException([
                'msg' => '更新类型失败',
                'error_code' => '50003'
            ]);
        }
        $data = Config::pull('type_'.$params['field']);
        if($data) {
            $mark = true;
            foreach ($params['value'] as $val){
                if(!in_array($val, $data)){
                    $mark = false;
                }
            }
            if(!$mark) {
                self::setTypeCache($params['field'], $params);
            }
        } else {
            self::setTypeCache($params['field'], $params);
        }
        return writeJson(201, [], '更新类型成功');
    }

    /**
     * 删除类型
     * @param('ids','待删除的类型id列表','require|array|min:1')
     * @auth('删除类型','类型管理')
     * @return \think\response\Json
     */
    public function delType()
    {
        $ids = Request::delete('ids');
        Db::startTrans();
        array_map(function ($id) {
            $type = TypeModel::get($id);
            if(!$type) {
                throw new TypeException([
                    'msg' => 'id为' . $id . 'Type不存在'
                ]);
            }
            try{
                Cache::rm('type_'.$type['field']);
                $type->delete();
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }
        }, $ids);
        Hook::listen('logger', '删除了id为' . implode(',', $ids) . '类型');
        return writeJson(201, [], '类型删除成功');
    }

    /**
     * 获取类型字段值
     * @param('field','字段值','require')
     */
    public function getFieldValue($field="")
    {
        if(!isset($field) || empty($field)) {
            return [];
        }
        $fieldArr = explode(",",$field);
        $result = [];
        foreach ($fieldArr as $field) {
            $data = Cache::get('type_'.$field);
            if(!$data) {
                try {
                    $data = TypeModel::where('field', $field)->find();
                } catch (Exception $e) {
                    throw new TypeException([
                        'msg' => '类型获取失败',
                        'error_code' => '50002'
                    ]);
                }
                if($data) {
                    self::setTypeCache($field, $data);
                }
            }
            if(!empty($data) || $data) {
                array_push($result, $data);
            }
        }
        return $result;
    }

    /**
     * 设置缓存
     * @param string $name
     * @param $value
     * @return mixed
     */
    public static function setTypeCache($name="", $value)
    {
        if(!$value || !$name) return
        Cache::set('type_'.$name, $value, 3600);
    }


}