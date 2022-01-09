<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use app\lib\exception\setting\SettingException;
use think\facade\Request;
use app\api\model\Setting as SettingModel;

class Setting extends Base
{
    /**
     * 获取全部设置信息
     * @return mixed
     * @throws CulturalException
     */
    public function getSettings()
    {
        $result = SettingModel::select();
        return $result;
    }

    /**
     * 新建设置
     * @param('names', '栏目名称', 'require')
     * @param('values', '栏目值', 'require')
     * @return \think\response\Json
     */
    public function create()
    {
        $params = Request::post();
        $settingData = SettingModel::get([
            'names' => $params['names']
        ]);
        if($settingData) {
            return $this->update($params);
        }
        $result = SettingModel::create($params, true);
        if (!$result) {
            throw new SettingException([
                'msg' => '创建设置失败',
                'error_code' => '83004'
            ]);
        }
        return writeJson(201, [], '新增设置成功');
    }


    /**
     * 更新设置
     * @return \think\response\Json
     */
    public function update($params)
    {
        $SettingModel = new SettingModel();
        $result = $SettingModel->save($params, ['names' => $params['names']]);
        if(!$result) {
            throw new SettingException([
                'msg' => '更新设置失败',
                'error_code' => '83005'
            ]);
        }
        return writeJson(201, [], '更新设置成功');
    }

    /**
     * 清空缓存
     * @param('names','缓存键名','require|array')
     * @param $names
     */
    public function clearCache($names)
    {
        foreach ($names as $val) {
            if(cache($val)) {
                cache($val, null);
            }
        }
        return writeJson(201, [], '更新设置成功');
    }
}