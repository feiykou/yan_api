<?php


namespace app\api\validate\customer;


use LinCmsTp5\validate\BaseValidate;

class CustomerForm extends BaseValidate
{
    protected $rule = [
        'name' => 'require',
        'contacts_name' => 'require',
//        'telephone' => 'mobile',
        'email' => 'email',
//        'address' => 'require',
        'purpose' => 'require',
        'channel' => 'require'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }

    public function sceneFilter()
    {

    }
}