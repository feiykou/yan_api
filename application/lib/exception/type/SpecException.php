<?php


namespace app\lib\exception\type;


use LinCmsTp5\exception\BaseException;

class TypeException extends BaseException
{
    public $code = 400;
    public $msg = '类型通用错误';
    public $error_code = '50000';
}