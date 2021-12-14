<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class Spec extends BaseModel
{
    use SoftDelete;
    protected $hidden = ['create_time', 'delete_time', 'update_time'];

    public function items()
    {
        return $this->hasMany('spec_value', 'spec_id', 'id');
    }

    public static function getSpecPaginate()
    {
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->count();
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
     * 获取单个规格，和关联的规格值
     * @param $id
     * @return \think\db\Query|null
     */
    public static function getSpec($id)
    {
        $result = self::with('items')
            ->get($id);
        return $result;
    }

}