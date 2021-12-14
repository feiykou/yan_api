<?php


namespace app\api\validate\customer;


use LinCmsTp5\validate\BaseValidate;

class CustomerForm extends BaseValidate
{
    protected $rule = [
        'title' => 'require',
        'img' => 'require',
        'online' => 'boolean',
        'top_resc' => 'boolean',
        'order' => 'number'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }
}