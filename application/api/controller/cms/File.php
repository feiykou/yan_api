<?php
/*
* Created by DevilKing
* Date: 2019- 06-08
*Time: 16:26
*/

namespace app\api\controller\cms;

use think\facade\Request;
use app\lib\file\LocalUploader;
use app\lib\exception\file\FileException;

/**
 * Class File
 * @package app\api\controller\cms
 */
class File
{
    /**
     * @return mixed
     * @throws FileException
     * @throws \LinCmsTp\exception\FileException
     */
    public function postFile()
    {
        try {
            $request = Request::file();
        } catch (\Exception $e) {
            var_dump($e);
            var_dump(111);
            throw new FileException([
                'msg' => '字段中含有非法字符',
            ]);
        }
        var_dump(1111);
        $file = (new LocalUploader($request))->upload();
        return $file;
    }
}
