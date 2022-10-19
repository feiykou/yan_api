<?php
/**
 * Created by PhpStorm.
 * User: 沁塵
 * Date: 2019/2/19
 * Time: 11:22
 */

namespace app\api\model;


use think\Model;

class BaseModel extends Model
{
    /**
     * 设置单张图片前缀域名
     * @param $value
     * @return string
     */
    protected function getImgAttr($value)
    {
        if(is_array($value)) {
            return $this->setMultiImgPrefix($value);
        }
        return $this->setImgPrefix($value);
    }

    /**
     * 设置单张图片更新去掉前缀域名
     * @param $value
     * @return string
     */
    protected function setImgAttr($value)
    {
        if(is_array($value)) {
            return $this->setMultiImgPrefix($value);
        }
        return $this->cancelImgPrefix($value);
    }

    protected function setImgPrefix($value, $host='')
    {
        if (empty($value)){
            return '';
        }
        if (strstr($value, 'http')) return $value;
        $host = empty($host) ? config('setting.img_prefix') : $host;
        return $host . $value;
    }

    protected function cancelImgPrefix($value)
    {
        if (empty($value)){
            return '';
        }
        if (!strstr($value, 'http')) return $value;
        return str_replace(config('setting.img_prefix'), '', $value);
    }

    protected function setMultiImgPrefix($value, $host = '')
    {
        if(empty($value) || !is_array($value)) {
            return;
        }

        foreach ($value as $k => &$v) {
            if(is_object($v)) $v = (array)$v;
            if(!is_array($v)) {
                $v = $this->setImgPrefix($v, $host);
            } else {
                $v['src'] = $this->setImgPrefix($v['src'], $host);
            }
        }
        return $value;
    }

    protected function setMultiFilePrefix($value, $host = '')
    {
        if(empty($value) || !is_array($value)) {
            return;
        }
        foreach ($value as $k => &$v) {
            if(is_object($v)) $v = (array)$v;
            if(!is_array($v)) {
                $v = $this->setImgPrefix($v, $host);
            } else if(isset($v['path'])) {
                $v['path'] = $this->setImgPrefix($v['path'], $host);
            }
        }
        return $value;
    }

    protected function cancelMultiImgPrefix($value)
    {
        if(empty($value) || !is_array($value)) {
            return;
        }
        foreach ($value as $k => &$v) {
            if(is_object($v)) $v = (array)$v;
            if(!is_array($v)) {
                $v = $this->cancelImgPrefix($v);
            } else {
                $v['src'] = $this->cancelImgPrefix($v['src']);
            }
        }
        return $value;
    }

    /**
     * 构造条件为相等的数组查询条件
     * @param $field    要检索的参数名数组
     * @param $params   前端提交过来的所有GET参数数
     * @return array    构造好后的查询条件
     */
    protected static function equalQuery($field, $params)
    {
        $query = [];
        foreach ($field as $value) {
            if (is_array($value)) {
                if (array_key_exists($value[0], $params)) {
                    $query[] = [$value[1], '=', $params[$value[0]]];
                }
            } else {
                if (array_key_exists($value, $params)) {
                    if($value == 'name' || $value == 'user_code' || $value == 'customer_name' || $value == 'contacts_name' || $value == 'telephone') {
                        $query[] = [$value, 'like', '%'.$params[$value].'%'];
                    } else {
                        $query[] = [$value, '=', $params[$value]];
                    }
                }
            }
        }
        return $query;
    }


    /**
     * 查询时间区间的数据
     * @param $startField       开始时间的参数名
     * @param $endField         结束时间的参数名
     * @param $params           前端提交过来的所有GET参数数组
     * @param string $dbField   要查询的表字段名，默认是create_time
     * @return array
     */
    protected static function betweenTimeQuery($startField, $endField, $params, $dbField = 'create_time')
    {
        $query = [];
        if (array_key_exists($startField, $params) && array_key_exists($endField, $params)) {
            if (!empty($params[$startField]) && !empty($params[$endField])) {
                $query = array($dbField, 'between time', array($params[$startField], $params[$endField]));
            }
        }
        return $query;
    }


}