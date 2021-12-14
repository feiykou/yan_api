<?php


namespace app\lib\exception\customer_report;


use LinCmsTp5\exception\BaseException;

class CustomerReportException extends BaseException
{
    public $code = 400;
    public $msg  = 'CustomerReport通用错误';
    public $error_code = '51000';
}