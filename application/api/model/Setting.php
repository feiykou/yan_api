<?php


namespace app\api\model;


use LinCmsTp5\exception\BaseException;
use think\facade\Cache;
use think\facade\Config;

class Setting extends BaseModel
{
    protected $json = ['values'];

    /**
     * 获取某一类值
     * @param $name
     */
    public static function getSettingValuesData($name)
    {
        $data = self::where([
            'names' => $name . 'Form'
        ])->find();
        return $data;
    }

    /**
     * 获取设置并缓存
     * @param $name
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws BaseException
     */
    public static function getNameCache($name)
    {
        $nameKey = $name . 'Form';
        $data = Config::pull($nameKey);
        if(!$data) {
            $data = self::getSettingValuesData($name);
            if(!$data) {
                throw new BaseException([
                    'msg' => '未找到当前小程序信息'
                ]);
            }
            Cache::set($nameKey, $data['values']);
        }
        return (array)$data['values'];
    }

    /**
     * 获取设置字段值
     * @param $name
     * @param $Fields
     * @return array
     * @throws BaseException
     */
    public static function getFieldCache($name, $Fields)
    {
        $data = self::getNameCache($name);
        if(!$data) {
            return [];
        }
        $result = [];
        $Fields = explode(',', $Fields);
        foreach ($Fields as $key) {
            $key = trim($key);
            if(isset($data[$key]) && $data[$key]) {
                $result[$key] = $data[$key];
            }
        }
        if(empty($result)) {
            throw new BaseException([
                'msg' => '订阅消息参数为空'
            ]);
        }
        return $result;
    }

    /**
     * 获取指定类型的订阅消息
     * @param string $type
     * @return array
     * @throws BaseException
     */
    public static function getMessageData($type='order')
    {
        $messageData = self::getNameCache('message');
        $data = [];
        foreach ($messageData as $key=>$val) {
            if(strpos($key, $type)) {
                $data[$key] = $val;
            }
        }
        return $data;
    }

    /**
     * 获取订阅消息指定字段的所有值集合
     * @param array $message
     * @param string $field
     * @return array
     * @throws BaseException
     */
    public static function getMessageTypeValue($type="order", $field="templateID")
    {
        $data = [];
        $message = self::getMessageData($type);
        foreach ($message as $key => $val) {
            if($val[$field]) {
                array_push($data, $val[$field]);
            }
        }
        if(empty($data)) {
            throw new BaseException([
                'msg' => '模板为空'
            ]);
        }
        return $data;
    }
}