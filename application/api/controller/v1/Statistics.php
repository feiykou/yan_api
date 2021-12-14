<?php


namespace app\api\controller\v1;


use app\api\controller\Base;
use think\facade\Request;
use app\api\service\Statistics as StatisticsService;

class Statistics extends Base
{
    /**
     * 获取客户数据基础统计
     * @param('start','开始时间','require|date')
     * @param('end','结束时间','require|date')
     * @return array
     */
    public function getCustomerBaseStatistics()
    {
        $params = Request::get();
        $result = StatisticsService::getCustomerStatisticsByDate($params);
        return $result;
    }

    /**
     * 获取各渠道来源客户数
     */
    public function getCustomerChannelData()
    {
        $result = StatisticsService::getCustomerChannelData();
        return $result;
    }

    /**
     * 未跟进客户数统计
     * @return mixed
     */
    public static function getCustomerNoFollow()
    {
        $params=[];
        $params['start'] = date('Y-m-d',strtotime("-16 day", time()));
        $params['end'] = date('Y-m-d',strtotime("-3 day", time()));
        $result = StatisticsService::getCustomerNoFollowByDate($params);
        return $result;
    }

    /**
     * 跟进客户统计
     * @return mixed
     */
    public static function getCustomerFollowByDate()
    {
        $params=[];
        $params['start'] = date('Y-m-d',strtotime("-16 day", time()));
        $params['end'] = date('Y-m-d',strtotime("0 day", time()));
        $result = StatisticsService::getCustomerFollowByDate($params);
        return $result;
    }


    /**
     * 获取首页统计数字
     * 总客户数量
     * 新增用户数
     * 公域池客户数量
     * 3天未维护客户数量
     */
    public function getIndexData()
    {
    }



    /**
     * 指定时间范围统计订单基础数据
     * @param('start','开始时间','require|date')
     * @param('end','结束时间','require|date')
     * @param('type','日期间距类型','require')
     */
    public function getOrderBaseStatistics()
    {
       $params = Request::get();
       $result = StatisticsService::getCustomerStatisticsByDate($params);
       return $result;
    }




}