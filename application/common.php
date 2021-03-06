<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\lib\auth\AuthMap;
use LinCmsTp5\exception\ParameterException;
use think\facade\Request;

/**
 * @param $code
 * @param $errorCode
 * @param $data
 * @param $msg
 * @return \think\response\Json
 */

function writeJson($code, $data, $msg = 'ok', $errorCode = 0)
{
    $data = [
        'error_code' => $errorCode,
        'result' => $data,
        'msg' => $msg
    ];
    return json($data, $code);
}

function rand_char($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

function split_modules($auths, $key = 'module')
{
    if (empty($auths)) {
        return [];
    }

    $items = [];
    $result = [];

    foreach ($auths as $key => $value) {
        if (isset($items[$value['module']])) {
            $items[$value['module']][] = $value;
        } else {
            $items[$value['module']] = [$value];
        }
    }
    foreach ($items as $key => $value) {
        $item = [
            $key => $value
        ];
        array_push($result, $item);
    }
    return $result;

}

/**
 * @param $auth
 * @return array
 * @throws ReflectionException
 */
function findAuthModule($auth)
{
    $authMap = (new AuthMap())->run();
    foreach ($authMap as $key => $value) {
        foreach ($value as $k => $v) {
            if ($auth === $k) {
                return [
                    'auth' => $k,
                    'module' => $key
                ];
            }
        }
    }
}

/**
 * @return array
 * @throws ParameterException
 */
function paginate()
{
    $count = intval(Request::get('count'));
    $start = intval(Request::get('page'));

    $count = $count >= 15 ? 15 : $count;

    $start = $start * $count;

    if ($start < 0 || $count < 0) throw new ParameterException();

    return [$start, $count];
}

function fill_date_range($queryStart, $queryEnd, $format, $stepType, $extend = '', $step = 1)
{
    $range = [];
    $rangeStart = strtotime($queryStart);
    $rangeEnd = strtotime($queryEnd);
    while ($rangeStart <= $rangeEnd) {
        // 利用PHP内置函数date()按$format参数格式化时间戳
        $formattedDate = date($format, $rangeStart);
        $item = [
            'date' => $formattedDate,
            'count' => 0
        ];
        if ($extend) $item[$extend] = 0;
        array_push($range, $item);
        // 计算
        $rangeStart = strtotime("+{$step} {$stepType}", $rangeStart);
    }
    return $range;
}

function getArrKey($field = '')
{
    return $field;
}


/**
 * 如果是2023年，则让id自动从230000开始算起
 * @return false|int
 */
function setIdCode() {
    $nextYearTime = strtotime("1 January 2023");
    $nowTime = strtotime('now');
    $nowYear = date('Y-m-d h:i:sa',$nowTime);
    if($nowTime >= $nextYearTime && $nowYear != '2023') {
        return 230000;
    }
    return false;
}