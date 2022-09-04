<?php


namespace app\api\model;


use think\Db;
use think\Exception;
use think\migration\command\migrate\Rollback;
use think\model\concern\SoftDelete;

class Customer extends BaseModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    protected $json = ['address'];

    public function getStatusTextAttr($value,$data)
    {
        var_dump($value);
        var_dump($data);
//        $status = [0=>'禁用',1=>'正常',2=>'待审核'];
//        return $status[$data['status']];
    }

    /**
     * 客户日志一对多
     * @return \think\model\relation\HasMany
     */
    public function customerLog()
    {
        return $this->hasMany('customer_log', 'customer_id', 'id');
    }

    /**
     * 客户待办一对多
     * @return \think\model\relation\HasMany
     */
    public function customerDealt()
    {
        return $this->hasOne('customer_dealt', 'customer_id', 'id');
    }

    /**
     * 一对一
     * @return \think\model\relation\HasOne
     */
    public function customerMain()
    {
        return $this->hasOne('customer_main', 'link_code', 'link_code');
    }

    /**
     * 一对多
     * @return \think\model\relation\HasOne
     */
    public function customerProject()
    {
        return $this->hasMany('customer_project', 'link_code', 'link_code');
    }


    /**
     * 获取所有分页信息
     * @return array
     * @throws \LinCmsTp5\exception\ParameterException
     */
    public static function getCustomerPaginate($uid='',$params=[])
    {
        $field = ['name', 'follow_status', 'id', 'author', 'contacts_name', 'telephone'];
        $query = self::equalQuery($field, $params);
        $query[] = self::betweenTimeQuery('start', 'end', $params, 'update_time');
        $whereJSON = [];
        if(isset($params['provice']) && !empty($params['provice'])) {
            $whereJSON[] = ['address->province','like', '%'.$params['provice'].'%'];
        }

        if(!empty($query)) {
            foreach ($query as $key => $val) {
                if(isset($val) && empty($val)) {
                    unset($query[$key]);
                }
            }
        }
        if(empty($query)) $query = [];

        if(isset($params['make_copy_user']) && $params['make_copy_user'] != '0' && $params['make_copy_user']) {
            $make_copy_user = $params['make_copy_user'];
            if(isset($params['type']) && $params['type']) {
                if(intval($params['type']) == 1) {
                    $condition = ['channel', '=', ''];
                } else {
                    $condition = ['channel', '<>', ''];
                }
                $query[] = $condition;
            }
            // 推广待办
            if($make_copy_user > 0) {
                // 管理员查看自己的
                $query[] = ['make_copy_user','=',$make_copy_user];
            } else if($make_copy_user == -1){
                // 管理员可以查看所有的
                $query[] = ['make_copy_user','<>',0];
            }
        } else {
            if($uid && $uid > 0) {
                $query[] = ['user_id','=',$uid];
            } else {
                if($uid == -1) {
                    // 释放的客户
                    $query[] = ['user_id','<>',0];
                } else {
                    $query[] = ['user_id','=',0];
                }
            }
        }

        list($start, $count) = paginate();
        $listData = new self();

        // 如果是待办
        if(array_key_exists('make_copy_user',$params)) {
            $listData = $listData->with(['customerDealt']);
        }
        // 软删除  0:要查询软删除
        if(isset($params['soft_del']) && $params['soft_del'] == 0) {
            $totalNums = $listData->onlyTrashed()->where($query)->where($whereJSON)->count();
            $listData = $listData
                ->onlyTrashed()
                ->limit($start, $count)
                ->where($query)
                ->where($whereJSON)
                ->order(['create_time' => 'desc', 'id' => 'desc'])
                ->select();
        } else {
            $totalNums = $listData->where($query)->where($whereJSON)->count();
            $listData = $listData
                ->limit($start, $count)
                ->where($query)
                ->where($whereJSON)
                ->order(['create_time' => 'desc', 'id' => 'desc'])
                ->select();
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
    public static function getCustomerDetail($id,$delfield='')
    {
        $where = [
            'id' => $id
        ];
        $result = self::where($where)
            ->hidden([$delfield])
            ->with(['customerMain'])
            ->find();
        return $result;
    }

    /**
     * 新客超3天未跟进
     */
    public static function passNoFollowData($uid = '')
    {
        // 获取3天前时间
        $startTime = date('Y-m-d');
        $startTime = strtotime($startTime);
        $noFollowTime = date('Y-m-d',strtotime("-3 day", $startTime));
        $query = [];
        $query[] = ['follow_time', '<', $noFollowTime];
        if(isset($uid) && $uid > 0) {
           $query[] = ['user_id','=',$uid];
        } else{
           $query[] = ['user_id','<>',0];
        }
        list($start, $count) = paginate();
        $listData = new self();
        $totalNums = $listData->where($query)->count();
        $listData = $listData->limit($start, $count)
            ->where($query)
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
     * 通过link_code获取客户信息
     * @param string $linkCode
     * @param string[] $field  客户信息字段
     * @return array|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCustomerByLinkCode($linkCode='', $field=['id', 'name'])
    {
        $result = self::where(['link_code' => $linkCode])
            ->field($field)
            ->find();
        if(!$result) return [];
        return $result;
    }

    /**
     * 更新跟进时间
     */
    public static function updateFollowTime($customer_id = 0)
    {
        $curTime = (new self())->formatDateTime('Y-m-d H:i:s.u');
        $result = self::where('id', $customer_id)
            ->update(['follow_time' => $curTime]);
        return $result;
    }

    /**
     * 抢单时，修改客户和项目管理员
     */
    public static function updateUserIDAndProject($customer_id, $param=[])
    {
        if(!isset($customer_id) || empty($customer_id)) return false;
        Db::startTrans();
        try {
            $customer = self::field('id,link_code')->get($customer_id);
            $ids = CustomerProject::where(['link_code'=>$customer['link_code']])
                ->field('id')
                ->select()
                ->toArray();
            $projectIDs = [];
            if(count($ids) > 0) {
                foreach ($ids as $val) {
                    array_push($projectIDs, [
                        'id' => $val['id'],
                        'user_id' => $param['user_id'],
                        'author' => $param['author']
                    ]);
                }
            }
            if(count($projectIDs) > 0) {
                $project = new CustomerProject;
                $project->saveAll($projectIDs);
            }
            $result = self::where('id', $customer_id)->update($param);
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            return false;
        }
    }


    /**
     * 未跟进客户数
     *  3-5天  6-8天  9-11天  12-14天
     */
    public static function getNoFollowByDate($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $customer = self::where($query)
            ->field("DATE_FORMAT(follow_time,'{$format}') as date,
                        count(*) as count")
            ->group('date')
            ->select();
        return $customer;
    }

    /**
     * 获取用户数据分析数据
     * @param $params
     * @param $format
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCustomerStatisticsByDate($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $customer = self::where($query)
            ->field("DATE_FORMAT(create_time,'{$format}') as date,
                        count(*) as count")
            ->group('date')
            ->select();
        return $customer;
    }

    /**
     * 获取各渠道来源客户数
     */
    public static function getCustomerChannelByDate()
    {
        $query = [];
        $user = self::where($query)
            ->field("channel,count(*) as count")
            ->group('channel')
            ->select();
        return $user;
    }

    /**
     * 总客户数 统计
     */
    public static function totalCustomerNum($params, $format)
    {
        $query = [];
        $query[] = self::betweenTimeQuery('start', 'end', $params);
        $titalNum = self::where($query)
            ->field("DATE_FORMAT(create_time,'{$format}') as date,count(*) as count")
            ->group('date')
            ->select();
        return $titalNum;
    }

    /**
     * 公域池 统计
     */
    public static function publicCustomerNum()
    {
        $titalNum = self::where(['user_id'=> 0])
            ->field("count(*) as count")
            ->group('user_id')
            ->select();
        return $titalNum;
    }

    /**
     * 获取客户信息，客户主信息，客户项目信息
     * @param array $ids
     * @return false|\PDOStatement|string|\think\Collection|\think\db\Query[]|\think\model\Collection
     * @throws \think\Exception\DbException
     */
    public static function getCustomerAndProject($uid=0,$params)
    {
        $field = ['name', 'follow_status', 'id', 'author', 'contacts_name', 'telephone'];
        $query = self::equalQuery($field, $params);
        $query[] = self::betweenTimeQuery('start', 'end', $params, 'update_time');
        $whereJSON = [];
        if(isset($params['provice']) && !empty($params['provice'])) {
            $whereJSON[] = ['address->province','like', '%'.$params['provice'].'%'];
        }

        if(!empty($query)) {
            foreach ($query as $key => $val) {
                if(isset($val) && empty($val)) {
                    unset($query[$key]);
                }
            }
        }
        if(empty($query)) $query = [];
        if($uid && $uid > 0) {
            $query[] = ['user_id','=',$uid];
        } else {
            $query[] = ['user_id','neq',0];
        }
        $result = self::with(['customerMain', 'customerProject'])
            ->where($query)
            ->order('id', 'desc')
            ->select();
        return $result;
    }

    /**
     * 根据日志跟进状态更新客户状态
     */
    public static function updateCustomerStatus($customer_id, $status='')
    {
        $result = self::where(['id'=>$customer_id])->update([
            'follow_status' => $status
        ]);
        return $result;
    }


}