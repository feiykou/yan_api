<?php


namespace app\lib\exception\customer_log;


use LinCmsTp5\exception\BaseException;

class CustomerLogException extends BaseException
{
    public $code = 400;
    public $msg  = 'Customer日志通用错误';
    public $error_code = '51000';
}