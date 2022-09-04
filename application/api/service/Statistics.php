<?php


namespace app\api\service;


use app\api\model\Customer as CustomerModel;
use app\api\model\CustomerLog as CustomerLogModel;
use app\api\model\Order as OrderModel;
use app\api\model\User as UserModel;

class Statistics
{
//    /**
//     * 指定时间范围统计订单基础数据
//     * @param $params
//     * @return mixed
//     */
//    public static function getOrderBaseStatistics($params)
//    {
//        $format = self::handleType($params['type']);
//        $statisticRes = OrderModel::getOrderStatisticsByDate($params, $format['mysql']);
//        $range = fill_date_range($params['start'], $params['end'], $format['php'], $params['type'], 'total_price');
//        $result = self::handleReturn($statisticRes, $range);
//        return $result;
//    }

    /**
     * 指定时间范围统计客户基础数据
     * @param $params
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCustomerStatisticsByDate($params)
    {
        $format = self::handleType($params['type']);
        $statisticRes = CustomerModel::getCustomerStatisticsByDate($params, $format['mysql']);
        $range = fill_date_range($params['start'], $params['end'], $format['php'], $params['type']);
        $result = self::handleReturn($statisticRes, $range);
        return $result;
    }

    public static function getTotalCustomers($params) {
        $format = self::handleType('day');
        $statisticRes = CustomerModel::totalCustomerNum($params, $format['mysql']);
        // 获取总客户数
        $totalNum = 0;
        // 最近一个月
        $recentMonth = strtotime("-60 day", time());
//        var_dump(date('Y-m-d',$recentMonth));
        // 超过3天
        $thanThreeMonth = strtotime("-3 day", time());
        $thanThreeNum = 0;
        $recentOneMonth = 0;
        foreach ($statisticRes as $item) {
            $totalNum += $item['count'];
            $dateNum = strtotime($item['date']);
            if($dateNum >= $recentMonth) {
                $recentOneMonth+= $item['count'];
            }
            if($dateNum < $thanThreeMonth) {
                $thanThreeNum += $item['count'];
            }
        }
        return [
            "totalNum" => $totalNum,
            "recentMonthNum" => $recentOneMonth,
            "thanThreeNum" => $thanThreeNum
        ];
    }

    /**
     * 公域池客户总量
     */
    public static function getPublicCustomers()
    {
        $result = CustomerModel::publicCustomerNum();
        $data = $result->toArray();
        if($data && count($data) > 0) {
            $data = $data[0];
        } else {
            $data = ['count' => 0];
        }
        return $data;
    }

    public static function getCustomerChannelData()
    {
        $statisticRes = CustomerModel::getCustomerChannelByDate();
        $statisticRes = $statisticRes->toArray();
        return $statisticRes;
    }

    /**
     * 未跟进客户数统计
     * @param $params
     * @return array
     */
    public static function getCustomerNoFollowByDate($params)
    {
//        $format = self::handleType('day');
//        $statisticRes = CustomerLogModel::getNoFollowByDate($format['mysql']);
//        $statisticRes = $statisticRes->toArray();
//        return $statisticRes;
        $format = self::handleType('day');
        $statisticRes = CustomerModel::getNoFollowByDate($params, $format['mysql']);
        $range = fill_date_range($params['start'], $params['end'], $format['php'], 'day', '', 1);
        $result = self::handleReturn($statisticRes, $range);
        return self::getResNoFollowCountByDate($result, $params);
    }

    /**
     * 每天跟进客户统计
     */
    public static function getCustomerFollowByDate($params)
    {
        $format = self::handleType('day');
        $result = CustomerLogModel::getCustomerFollowByDate($params, $format['mysql']);
        $range = fill_date_range($params['start'], $params['end'], $format['php'], 'day', '', 1);
        if ($result->isEmpty()) return $range;
        // 函数返回的数组元素顺序和原数组一致（重点）
        $rangeColumn = array_column($range, 'date');
        $statisticRes = $result->toArray();
        array_walk($statisticRes, function ($item) use (&$range, $rangeColumn) {
            $key = array_search($item['date'], $rangeColumn);
            $range[$key]['count'] += 1;
        });
        return $range;
    }

    // 未跟进统计，数据处理函数
    public static function getResNoFollowCountByDate($result, $params)
    {
        // 初始时长
        $endTime = date('d', strtotime($params['end']));
        $curDay = date('d');
        $startStep = $curDay - $endTime - 1;
        // 倒叙，开始的是最新的日期
        $result = array_reverse($result);
        // 初始化变量
        $formatData = [];
        $count = 0;
        $i = 1;
        foreach ($result as $key => $item) {
            $count += $item['count'];
            $i++;
            if($i > 3) {
                $arr = [
                    'name' => ($startStep+1).'-'.($startStep+3),
                    'count' => $count
                ];
                $startStep = $startStep + 3;
                $i = 1;
                $count = 0;
                array_push($formatData, $arr);
            }
        }
        return $formatData;
    }

    /**
     * 封装date()和FROM_UNIXTIME日期格式
     * @param $type
     * @return mixed
     */
    protected static function handleType($type)
    {
        $map = [
            'year' => [
                'php' => 'Y',
                'mysql' => '%Y'
            ],
            'month' => [
                'php' => 'Y-m',
                'mysql' => '%Y-%m'
            ],
            'day' => [
                'php' => 'Y-m-d',
                'mysql' => '%Y-%m-%d'
            ],
            'hour' => [
                'php' => 'Y-m-d-H',
                'mysql' => '%Y-%m-%d %H'
            ],
            'minute' => [
                'php' => 'Y-m-d H:i',
                'mysql' => '%Y-%m-%d %H:%i'
            ]
        ];
        return $map[$type];
    }

    /**
     * 封装数据公共的处理方法
     * @param $statisticRes
     * @param $range
     * @return mixed
     */
    protected static function handleReturn($statisticRes, $range)
    {
        if ($statisticRes->isEmpty()) return $range;
        // 函数返回的数组元素顺序和原数组一致（重点）
        $rangeColumn = array_column($range, 'date');
        $statisticRes = $statisticRes->toArray();
        array_walk($statisticRes, function ($item) use (&$range, $rangeColumn) {
            $key = array_search($item['date'], $rangeColumn);
            $range[$key] = $item;
        });
        return $range;
    }
}
