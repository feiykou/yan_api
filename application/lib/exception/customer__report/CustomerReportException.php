<?php


namespace app\lib\exception\customer;


use LinCmsTp5\exception\BaseException;

class CustomerReportException extends BaseException
{
    public $code = 400;
    public $msg  = 'Customer通用错误';
    public $error_code = '51000';
}