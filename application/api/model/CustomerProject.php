<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class CustomerProject extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    public function customer()
    {
        return $this->hasOne('customer','link_code', 'link_code');
//            ->bind([
//                'customer_name' => 'name'
//            ]);
    }

    public function customerMain()
    {
        return $this->hasOne('customer_main','link_code', 'link_code');
    }

    public function examine() {
        return $this->hasOne('customer_project_examine', 'project_id', 'id');
    }

    /**
     * 获取所有分页信息
     * @return array
     */
    public static function getPaginate($UID='', $customerID='', $params=[])
    {
        $field = ['name', 'follow_status', 'customer_name', 'user_code'];
        $where = self::equalQuery($field, $params);
        $timedbField = 'update_time';
        if(isset($params['follow_status']) && !empty($params['follow_status']) && $params['follow_status'] == config('setting.project_sucess_time')) {
            $timedbField = 'status_success_time';
        }
        $where[] = self::betweenTimeQuery('start', 'end', $params, $timedbField);

        if(!empty($where)) {
            foreach ($where as $key => $val) {
                if(isset($val) && empty($val)) {
                    unset($where[$key]);
                }
            }
        }
        if(empty($where)) $where = [];
        if(isset($customerID) && !empty($customerID)) $where[] = ['link_code', '=',intval($customerID)];
        if(isset($UID) && !empty($UID)) $where[] = ['user_id','=',$UID];
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($where)->count();
        $listData = $listData->limit($start, $count)
            ->where($where)
            ->order(['create_time' => 'desc', 'id' => 'desc'])
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
            ->with(['examine' => function($query) {
                $query->field(['id', 'project_id', 'status']);
            }])
            ->find();
        return $result;
    }

    /**
     * 通过ids获取项目及客户信息
     * @param array $ids
     * @return false|\PDOStatement|string|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\Exception\DbException
     */
    public static function getProjectAndCustomer($ids=[])
    {
        $result = self::with(['customerMain', 'customer'])
            ->order('id', 'desc')
            ->all($ids);
        return $result;
    }

    /**
     * 根据日志跟进状态更新客户项目状态
     */
    public static function updateCustomerProjectStatus($customer_project_id, $status='', $isFollow=false)
    {
        $incCount = $isFollow ? 1 : 0;
        $data = [];
        $data['follow_status'] = $status;
        if($status == config('setting.project_sucess_time')) {
            $dateTime = new \DateTime();
            $data['status_success_time'] = $dateTime->format('Y-m-d H:i:s');
        }
        $result = db('customer_project')->where('id',$customer_project_id)
            ->inc('follow_count', $incCount)
            ->update($data);
        return $result;
    }

    public static function upadteProjectAuthorAndID($customer_id, $author='', $user_id=0)
    {
        if(!isset($customer_id) || empty($customer_id)) return;
        $data = [];
        if($user_id) $data['user_id'] = $user_id;
        if($author) $data['author'] = $author;
    }


    /**
     * 每天跟进客户数统计
     */
    public static function getCustomerFollowByDate($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $customer = self::where($query)
            ->field("DATE_FORMAT(update_time,'{$format}') as date,
                        customer_id,
                        count(*) as count")
            ->group('date,customer_id')
            ->select();
        return $customer;
    }


    /**
     * 通过project_id获取customer_id，用在项目审核中
     */
    public static function getCustomerIDByProjectID($project_id=0)
    {
        if(!isset($project_id) || empty($project_id)) {
            return false;
        }
        $result = self::where('id', $project_id)
            ->with(['customer' => function($query) {
                $query->field('id, link_code');
            }])->find();
        if(!$result || !$result['customer']) {
            return false;
        }
        return $result['customer'];
    }
}