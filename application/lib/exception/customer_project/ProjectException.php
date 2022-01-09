<?php


namespace app\lib\exception\customer_project;


use LinCmsTp5\exception\BaseException;

class ProjectException extends BaseException
{
    public $code = 400;
    public $msg  = 'Customer项目通用错误';
    public $error_code = '51000';
}