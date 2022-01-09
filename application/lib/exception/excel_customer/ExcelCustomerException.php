<?php


namespace app\lib\exception\excel_customer;


use LinCmsTp5\exception\BaseException;

class ExcelCustomerException extends BaseException
{
    public $code = 400;
    public $msg  = '导入导出通用错误';
    public $error_code = '51000';
}