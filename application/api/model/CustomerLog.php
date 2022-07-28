<?php


namespace app\api\model;


use think\model\concern\SoftDelete;

class CustomerLog extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    protected $json = ['address', 'img_urls','file_urls'];

    // 设置spu_detail_img_list多图片链接
    protected function getImgUrlsAttr($value)
    {
        if(!$value) return;
        return $this->setMultiImgPrefix($value);
    }
    // 文件前缀
    protected function getFileUrlsAttr($value)
    {
        if(!$value) return;
        return $this->setMultiFilePrefix($value);
    }

    // 设置spu_detail_img_list多图片链接
    protected function setImgUrlsAttr(array $value)
    {
        return $this->cancelMultiImgPrefix($value);
    }

    protected function getContentAttr($value) {
//        html_entity_decode()
        return html_entity_decode($value);
    }

    public function getStatusTextAttr($value,$data)
    {
        var_dump($value);
        var_dump($data);
//        $status = [0=>'禁用',1=>'正常',2=>'待审核'];
//        return $status[$data['status']];
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customer()
    {
        return $this->hasOne('customer','id','customer_id')
            ->bind([
                'customer_name' => 'name',
                'telephone',
                'contacts_name',
                'address',
                'customer_user_code' => 'user_code',
                'channel'
            ]);
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customerMain()
    {
        return $this->hasOne('customer_main', 'user_id', 'id');
    }

    public function customerProject()
    {
        return $this->hasOne('customer_project', 'id','project_id')
            ->bind([
                'project_name' => 'name'
            ]);
    }

    /**
     * 获取所有分页信息
     * @return array
     */
    public static function getPaginate($uid=0, $params=[],$status=0)
    {
        $field = ['status', 'author'];
        $query = self::equalQuery($field, $params);
        $query[] = self::betweenTimeQuery('start', 'end', $params,'update_time');
        if(!empty($query)) {
            foreach ($query as $key => $val) {
                if(isset($val) && empty($val)) {
                    unset($query[$key]);
                }
            }
        }
        if(empty($query)) $query = [];
        // 如果customer_id和project_id都不存在，则获取日志失败
//        if(!isset($params['customer_id']) && !isset($params['project_id'])) return [
//            // 查询结果
//            'collection' => [],
//            // 总记录数
//            'total_nums' => 0
//        ];
        if($uid && $uid > 0) {
            $query[] = ['user_id','=',$uid];
        }
        if(isset($params['user_code']) && !empty($params['user_code'])) $query[] = ['user_code', '=', $params['user_code']];
        if(isset($params['project_id']) && !empty($params['project_id'])) $query[] = ['project_id','=',$params['project_id']];
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($query)->count();
        $listData = $listData->limit($start, $count)
            ->where($query)
            ->with(['customer', 'customerProject'])
            ->order(['create_time' => 'desc', 'id' => 'desc'])
            ->select();
        foreach ($listData as &$val) {
            if(empty($val['project_name'])) {
                $val['project_name'] = '日常维护';
            }
        }
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
            ->find();
        return $result;
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
     * 获取客户日志信息
     * @param array $ids
     */
    public static function getCustomerLogAndCustomer($ids=[])
    {
        $result = self::with(['customer', 'customerProject'])
            ->order('id', 'desc')
            ->all($ids);
        return $result;
    }
}