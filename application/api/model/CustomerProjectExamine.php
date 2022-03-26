<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class CustomerProjectExamine extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    public function project()
    {
        return $this->hasOne('customer_project', 'id', 'project_id')
            ->bind(['reason', 'project_name' => 'name', 'customer_name', 'project_customer_id' => 'customer_id']);
    }

    /**
     * 获取审核的项目
     * @return array
     */
    public static function getPaginate($UID='')
    {
        $where = [];
        if(isset($UID) && !empty($UID)) $where['user_id'] = $UID;
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($where)->count();
        $listData = $listData->limit($start, $count)
            ->where($where)
            ->with('project')
            ->order(['update_time' => 'desc','status' => 'desc'])
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
     * 获取详情
     */
    public static function getDetail($id)
    {
        $where = [
            'id' => $id
        ];
        $result = self::where($where)
            ->with('project')
            ->find();
        return $result;
    }
}