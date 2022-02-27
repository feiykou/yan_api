<?php


namespace app\api\controller;



use app\api\service\token\LoginToken;

class Base
{
    /**
     * 生成客户编码
     * @return string
     */
    public function makeCustomerCode()
    {
        $codeSn =
            str_replace('20','',intval(date('Y'))) . date('m') . sprintf(
                '%d', rand(1000, 9999));
        return json([
            'code' => $codeSn
        ]);
    }

    /**
     * 生成客户编码
     * @return string
     */
    public function makeLinkIndex()
    {
        $token = LoginToken::getInstance();
        $user_id = $token->getCurrentUID();
        $codeSn =
            $user_id.str_replace('20','',intval(date('Y'))) . date('m') . sprintf(
                '%02d', rand(10, 9999));
        return intval($codeSn);
    }
}