<?php


namespace app\api\validate\customer_log;


use LinCmsTp5\validate\BaseValidate;

class CustomerLogForm extends BaseValidate
{
    protected $rule = [
        'content' => 'require',
        'status' => 'require'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }
}