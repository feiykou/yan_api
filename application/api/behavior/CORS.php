<?php


namespace app\api\behavior;


class CORS
{
    public function appInit()
    {
        header('Access-Control-Allow-Origin: http://cms.szfxws.com');
        header("Access-Control-Allow-Headers: Authorization,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Expose-Headers: Authorization');
        header('Access-Control-Allow-Methods: PUT,DELETE,GET,POST');
        if(request()->isOptions()){
            exit();
        }
    }
}