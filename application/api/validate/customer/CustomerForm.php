<?php


namespace app\api\validate\customer;


use LinCmsTp5\validate\BaseValidate;

class CustomerForm extends BaseValidate
{
    protected $rule = [
//        'name' => 'require',
        'contacts_name' => 'require',
        'telephone' => 'unique:customer',
        'email' => 'email',
//        'address' => 'require',
        'purpose' => 'require',
//        'channel' => 'require'
    ];

    protected $message = [
        'telephone' => '同一个客户不能重复录入'
    ];

    public function sceneEdit()
    {
        return $this->append('id', 'require|number');
    }

    public function sceneFilter()
    {

    }
}