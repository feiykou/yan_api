<?php


namespace app\api\validate\project_examine;


use LinCmsTp5\validate\BaseValidate;

class CustomerProjectExamineForm extends BaseValidate
{
    protected $rule = [
        'status' => 'require',
        'project_id' => 'require | number',
        'customer_id' => 'require | number'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }
}