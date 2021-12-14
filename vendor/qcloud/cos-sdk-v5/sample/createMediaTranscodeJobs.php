<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$secretId = "SECRETID"; //"云 API 密钥 SecretId";
$secretKey = "SECRETKEY"; //"云 API 密钥 SecretKey";
$region = "ap-beijing"; //设置一个默认的存储桶地域
$cosClient = new Qcloud\Cos\Client(
    array(
        'region' => $region,
        'schema' => 'https', //协议头部，默认为http
        'credentials'=> array(
            'secretId'  => $secretId ,
            'secretKey' => $secretKey)));
try {
    // start --------------- 使用模版 ----------------- //
    $result = $cosClient->createMediaTranscodeJobs(array(
        'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
        'Tag' => 'Transcode',
        'QueueId' => 'asdadadfafsdkjhfjghdfjg',
        'CallBack' => 'https://example.com/callback',
        'Input' => array(
            'Object' => 'video01.mp4'
        ),
        'Operation' => array(
            'TemplateId' => 'asdfafiahfiushdfisdhfuis',
            'Output' => array(
                'Region' => $region,
                'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
                'Object' => 'video02.webm',
            ),
        ),
    ));
    // 请求成功
    print_r($result);
    // end --------------- 使用模版 ----------------- //


    // start --------------- 自定义参数 ----------------- //
    $result = $cosClient->createMediaTranscodeJobs(array(
        'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
        'Tag' => 'Transcode',
        'QueueId' => 'asdadadfafsdkjhfjghdfjg',
        'CallBack' => 'https://example.com/callback',
        'Input' => array(
            'Object' => 'video01.mp4'
        ),
        'Operation' => array(
            'Output' => array(
                'Region' => $region,
                'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
                'Object' => 'video01.mkv',
            ),
            'Transcode' => array(
                'Container' => array(
                    'Format' => 'mp4'
                ),
                'Video' => array(
                    'Codec' => 'H.264',
                    'Profile' => 'high',
                    'Bitrate' => '1000',
                    'Preset' => 'medium',
                    'Width' => '1280',
                    'Fps' => '30',
                ),
                'Audio' => array(
                    'Codec' => 'aac',
                    'Samplerate' => '44100',
                    'Bitrate' => '128',
                    'Channels' => '4',
                ),
                'TransConfig' => array(
                    'AdjDarMethod' => 'scale',
                    'IsCheckReso' => 'false',
                    'ResoAdjMethod' => '1',
                ),
                'TimeInterval' => array(
                    'Start' => '0',
                    'Duration' => '60',
                ),
            ),
        ),
    ));
    // 请求成功
    print_r($result);
    // end --------------- 自定义参数 ----------------- //
} catch (\Exception $e) {
    // 请求失败
    echo($e);
}

