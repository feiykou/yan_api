<?php
/**
 * Created by PhpStorm.
 * User: 沁塵
 * Date: 2019/4/28
 * Time: 14:18
 */

namespace app\index\controller;


class Index
{
    /**
     * 首次部署显示欢迎用的，部署完成后可以干掉这个index模块的整个目录
     * @return \think\Response
     */
    public function index()
    {
        phpinfo();
        return response('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: 
    pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: 
    "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; 
    margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"><p> 
    Lin <br/><span style="font-size:30px">心上无垢，林间有风。</span></p></div>');
    }
}