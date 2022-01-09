<?php


namespace app\api\validate\type;


use LinCmsTp5\validate\BaseValidate;

class TypeForm extends BaseValidate
{
    protected $rule = [
//        'content' => 'require'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }
}