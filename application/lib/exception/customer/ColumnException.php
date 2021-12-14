<?php


namespace app\lib\exception\column;


use LinCmsTp5\exception\BaseException;

class ColumnException extends BaseException
{
    public $code = 400;
    public $msg  = 'Column通用错误';
    public $error_code = '51000';
}