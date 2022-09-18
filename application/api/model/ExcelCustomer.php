<?php


namespace app\api\model;


class ExcelCustomer
{
    public static function handleExportData($data=[])
    {

//        $excelKeyData = ["follow_status","create_time","null","user_code","channel","address1","address2","contacts_name","telephone","customer_type","name",
//            ["customer_project","demand_desc"],["customer_project","follow_count"],["customer_project","reason"],["customer_project","scene"],
//            ["customer_project","industry"],["customer_project","product_type"],["customer_project","product_spec"],["customer_project","product_num"],
//            ["customer_project","product_price"],["customer_project","demand_bg"],["customer_project","solution"],["customer_project","install_solution"],["customer_project","product_lights"],
//            ["customer_project","custom_value"],["customer_project","follow_difficulty"],["customer_project","custom_feedback"],
//            ["customer_main","main_name"],["customer_main","main_contacts"],["customer_main","main_tel"],["customer_main","address_0"],
//            ["customer_main","address_1"],["customer_main","address_2"],["customer_main","main_spec_address"]
//        ];
        $excelData = [];
        $excelKeyData = [
            'A' => "follow_status",
            'B' => "create_time",
            'C' => "author",
            'D' => "id",
            'E' => "channel",
            'F' => "province",
            'G' => "city",
            'H' => "contacts_name",
            'I' => "telephone",
            'J' => "customer_type",
            'K' => "name",
            "customer_project" => [
                'L' => "name",
                'M' => "follow_status",
                'N' => "order_no",
                'O' => "status_success_time",
                'P' => "demand_desc",
                'Q' => "follow_count",
                'R' => "reason",
                'S' => "scene",
                'T' => "industry",
                'U' => "product_type",
                'V' => "product_spec",
                'W' => "product_num",
                'X' => "product_price",
                'Y' => "demand_bg",
                'Z' => "solution",
                'AA' => "install_solution",
                'AB' => "product_lights",
                'AC' => "custom_value",
                'AD' => "follow_difficulty",
                'AE' => "custom_feedback",
                'AF' => "project_channel"
            ],
            "customer_main" => [
                'AG' => "main_name",
                'AH' => "main_contacts",
                'AI' => "main_tel",
                'AJ' => "address_0",
                'AK' => "address_1",
                'AL' => "address_2",
                'AM' => "main_spec_address"
            ]
        ];
        $excel_index = $proJect_index = 2;
        foreach ($data as $index => $val) {
            $cacheData = [];
            // 多个项目数据
            $projectData = [];
            foreach ($excelKeyData as $key => $let ) {
                $curKey = $key . $excel_index;
                if(is_string($let) && (strstr($let, 'province') || strstr($let, 'city'))) {
                    $val['address'] = json_decode(json_encode($val['address']), true);
                    var_dump($val['address']);

                    if(strstr($let, 'province') && isset($val['address']['province']) ) {
                        $cacheData[$curKey] = $val['address']['province'];
                    }
                    if(strstr($let, 'city') && isset($val['address']['city']) ) {
                        $cacheData[$curKey] = $val['address']['city'];
                    }
                    var_dump($cacheData);
                } elseif ($key == 'customer_project') {
                    if(isset($val['customer_project']) && count($val['customer_project']) > 0) {
                        // 格式循环  $dataVal是project数据
                        foreach ($val['customer_project'] as $dataKey => $dataVal) {
                            // 项目1
                            if($dataKey >= 1) {
                                $proJect_index ++;
                            }
                            $proCache = [];
                            // $pkey是customer_project的key数组值， $pval是字段
                            foreach ($let as $pkey => $pval) {
                                $proCurKey = $pkey . $proJect_index;
                                if($dataKey >= 1) {
                                    $proCache[$proCurKey] = $dataVal[$pval];
                                } else {

                                    $cacheData[$proCurKey] = $dataVal[$pval];
                                }
                            }
                            if(count($proCache) > 0) {
                                array_push($projectData, $proCache);
                            }
                        }
                    }
                } elseif ($key == 'customer_main') {
                    $mainData = $val['customer_main'];
                    if(!empty($mainData) && count($mainData) > 0) {
                        foreach ($let as $mkey => $mval) {
                            $mainCurKey = $mkey . $excel_index;
                            if(strstr($mval, 'address_')) {
                                if(count($mainData['main_address']) == 0) {
                                    $cacheData[$mainCurKey] = '';
                                } else {
                                    $arr = explode('_',$mval);
                                    $addressIndex = $arr[1];
                                    if(isset($mainData['main_address'][$addressIndex])) {
                                        $cacheData[$mainCurKey] = $mainData['main_address'][$addressIndex]?:'';
                                    }else {
                                        $cacheData[$mainCurKey] = '';
                                    }
                                }
                            } else{
                                $cacheData[$mainCurKey] = $mainData[$mval];
                            }
                        }
                    }
                }
                else {
                    $cacheData[$curKey] = $let == 'null' ? '' : $val[$let];
                }
            }
            $proJect_index ++;
            $excel_index = $proJect_index;
            array_push($excelData, $cacheData);
            // 多个项目
            if(count($projectData) > 0) {
                $excelData = array_merge($excelData, $projectData);
            }
        }
        return $excelData;
    }

    public static function handleLogExportData($data=[])
    {
        $excelData = [];
        $excelKeyData = [
            'A' => "status",
            'B' => "author",
            'C' => "channel",
            'D' => "customer_id",
            'E' => "customer_name",
            'F' => "address_1",  // 1：省份
            'G' => "address_0",  // 0：城市
            'H' => "address_2",
            'I' => "contacts_name",
            'J' => "telephone",
            'K' => "null",
            'L' => "null",
            'M' => "project_id",
            'N' => "project_name",
            'O' => "create_time",
            'P' => "content",
            'Q' => "name",
        ];
        $excel_index = 2;
        foreach ($data as $index => $val) {
            $cacheData = [];
            foreach ($excelKeyData as $key => $let ) {
                $curKey = $key . $excel_index;
                if(is_string($let) && strstr($let, 'address')) {
                    $val['address'] = json_decode(json_encode($val['address']), true);
                    if(is_array($val['address'])) {
                        $address = array_values($val['address']);
                        if(count($address) == 0) {
                            $cacheData[$curKey] = '';
                        } else {
                            $arr = explode('_',$let);
                            $addressIndex = $arr[1];
                            if(!isset($address[$addressIndex])) {
                                $cacheData[$curKey] = '';
                            } else {
                                $cacheData[$curKey] = $address[$addressIndex];
                            }
                        }
                    }
                } else {
                    $cacheData[$curKey] = $let == 'null' ? '' : strip_tags($val[$let]);
                }
            }
            $excel_index ++;
            array_push($excelData, $cacheData);
        }
        return $excelData;
    }



}