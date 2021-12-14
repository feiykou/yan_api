<?php
/**
 * Created by PhpStorm.
 * User: 沁塵
 * Date: 2017/5/26
 * Time: 23:23
 */

namespace LinCmsTp5\admin\exception\token;

use LinCmsTp5\exception\BaseException;

class TokenException extends BaseException
{
    public $code = 401;
    public $msg = 'Token已过期或无效Token';
    public $error_code = 10000;
}