<?php

use qinchen\oss\exception\ConfigException;
use qinchen\oss\Manager;
use qinchen\oss\storage\StorageConfig;
use qinchen\oss\storage\tencent\Tencent;

/**
 * Created by PhpStorm
 * Author: 沁塵
 * Date: 2020/9/12
 * Time: 1:10 上午
 */
class TestTencentOss extends PHPUnit\Framework\TestCase
{
    /**
     * @var Tencent
     */
    private $storage;


    private function init()
    {
        $config = new StorageConfig("控制台查看获取", "控制台查看获取", "控制台查看获取");
        $this->storage = Manager::storage("tencent")
            ->init($config)
            ->bucket("存储桶名称");
    }

    /**
     * @test
     */
    public function get()
    {
        $this->init();
        $objectListInfo = $this->storage->get(10);
        $this->assertIsObject($objectListInfo, $objectListInfo);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function put()
    {
        $path = "带扩展名的完整文件路径";
        $this->init();
        $result = $this->storage->put("test.jpg", $path);
        $this->assertIsObject($result);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function putPart()
    {
        $path = "带扩展名的完整文件路径";

        $this->init();
        $result = $this->storage->putPart("test.jpg", $path);
        $this->assertIsObject($result);
    }

    /**
     * @test
     */
    public function delete()
    {
        $keys = ['test.jpg'];
        $this->init();
        $delete = $this->storage->delete($keys);
        $this->assertIsObject($delete);
    }

}