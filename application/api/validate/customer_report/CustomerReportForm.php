<?php


namespace app\api\validate\customer_report;


use LinCmsTp5\validate\BaseValidate;

class CustomerReportForm extends BaseValidate
{
    protected $rule = [
        'name' => 'require',
        'user_id' => 'require|number'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }
}