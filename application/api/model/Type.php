<?php


namespace app\api\model;


use LinCmsTp5\exception\BaseException;
use think\facade\Cache;
use think\facade\Config;
use think\model\concern\SoftDelete;

class Type extends BaseModel
{
    use SoftDelete;
    protected $json = ['value'];
    protected $hidden = ['create_time', 'delete_time', 'update_time'];

    public static function getTypePaginate()
    {
        $query = [];
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($query)->count();
        $listData = $listData->limit($start, $count)
            ->order(['create_time' => 'desc'])
            ->select();
        $result = [
            // 查询结果
            'collection' => $listData,
            // 总记录数
            'total_nums' => $totalNums
        ];
        return $result;
    }

    /**
     * 获取设置并缓存
     * @param $name
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws BaseException
     */
//    public static function getNameCache($name)
//    {
//        $nameKey = $name . 'Form';
//        $data = Config::pull($nameKey);
//        if(!$data) {
//            $data = self::getSettingValuesData($name);
//            if(!$data) {
//                throw new BaseException([
//                    'msg' => '未找到当前小程序信息'
//                ]);
//            }
//            Cache::set($nameKey, $data['values']);
//        }
//        return (array)$data['values'];
//    }

}