<?php


namespace app\api\validate\customer_project;


use LinCmsTp5\validate\BaseValidate;

class CustomerProjectForm extends BaseValidate
{
    protected $rule = [
//        'content' => 'require'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }
}