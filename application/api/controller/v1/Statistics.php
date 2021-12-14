<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use think\facade\Request;
use app\api\service\Statistics as StatisticsService;

class Statistics extends Base
{
    /**
     * 指定时间范围统计订单基础数据
     * @param('start','开始时间','require|date')
     * @param('end','结束时间','require|date')
     * @param('type','日期间距类型','require')
     */
    public function getOrderBaseStatistics()
    {
       $params = Request::get();
       $result = StatisticsService::getOrderBaseStatistics($params);
       return $result;
    }

    /**
     * 获取会员数据基础统计
     * @param('start','开始时间','require|date')
     * @param('end','结束时间','require|date')
     * @return array
     */
    public function getUserBaseStatistics()
    {
        $params = Request::get();
        $result = StatisticsService::getUserStatisticsByDate($params);
        return $result;
    }

    /**
     * 获取当前用户的所有订单
     * @param('uid','用户id','require|number')
     * @param $uid
     */
    public function getUserOrderData($uid)
    {
        $params = Request::get();
        $result = \app\api\model\Order::getUserOrdersPaginate($params);
        return $result;
    }

}