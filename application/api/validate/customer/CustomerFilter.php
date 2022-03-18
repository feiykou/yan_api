<?php


namespace app\api\validate\customer;


use LinCmsTp5\validate\BaseValidate;

class CustomerFilter extends BaseValidate
{
    protected $rule = [
        'start|开始时间' => 'date',
        'end|结束时间' => 'date',
        'name|客户名' => 'chs',
        'user_code|客户编码' => 'alphaNum'
    ];
//|length:8
}