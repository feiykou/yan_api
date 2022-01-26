<?php


namespace app\api\validate\customer_project;


use LinCmsTp5\validate\BaseValidate;

class CustomerProjectFilter extends BaseValidate
{
    protected $rule = [
        'start|开始时间' => 'date',
        'end|结束时间' => 'date',
        'name|客户名' => 'chs'
    ];
}