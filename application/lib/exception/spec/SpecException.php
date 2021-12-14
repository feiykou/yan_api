<?php


namespace app\lib\exception\spec;


use LinCmsTp5\exception\BaseException;

class SpecException extends BaseException
{
    public $code = 400;
    public $msg = '规格通用错误';
    public $error_code = '50000';
}