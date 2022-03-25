<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;

Route::group('', function () {
    Route::group('cms', function () {
        // 账户相关接口分组
        Route::group('user', function () {
            // 登陆接口
            Route::post('login', 'api/cms.User/login');
            // 刷新令牌
            Route::get('refresh', 'api/cms.User/refresh');
            // 查询自己拥有的权限
            Route::get('auths', 'api/cms.User/getAllowedApis');
            // 注册一个用户
            Route::post('register', 'api/cms.User/register');
            // 更新头像
            Route::put('avatar','api/cms.User/setAvatar');
            // 查询自己信息
            Route::get('information','api/cms.User/getInformation');
            // 用户更新信息
            Route::put('','api/cms.User/update');
            // 修改自己密码
            Route::put('change_password','api/cms.User/changePassword');
        });
        // 管理类接口
        Route::group('admin', function () {
            // 查询所有权限组
            Route::get('group/all', 'api/cms.Admin/getGroupAll');
            // 查询一个权限组及其权限
            Route::get('group/:id', 'api/cms.Admin/getGroup');
            // 删除一个权限组
            Route::delete('group/:id', 'api/cms.Admin/deleteGroup');
            // 更新一个权限组
            Route::put('group/:id', 'api/cms.Admin/updateGroup');
            // 新建权限组
            Route::post('group', 'api/cms.Admin/createGroup');
            // 查询所有可分配的权限
            Route::get('authority', 'api/cms.Admin/authority');
            // 删除多个权限
            Route::post('remove', 'api/cms.Admin/removeAuths');
            // 添加多个权限
            Route::post('/dispatch/patch', 'api/cms.Admin/dispatchAuths');
            // 查询所有用户
            Route::get('users', 'api/cms.Admin/getAdminUsers');
            // 修改用户密码
            Route::put('password/:uid', 'api/cms.Admin/changeUserPassword');
            // 删除用户
            Route::delete(':uid', 'api/cms.Admin/deleteUser');
            // 更新用户信息
            Route::put(':uid', 'api/cms.Admin/updateUser');

        });
        // 日志类接口
        Route::group('log',function (){
            Route::get('', 'api/cms.Log/getLogs');
            Route::get('users', 'api/cms.Log/getUsers');
            Route::get('search', 'api/cms.Log/getUserLogs');
        });
        //上传文件类接口
        Route::post('file','api/cms.File/postFile');
    });
    Route::group('v1', function () {
        Route::group('customer',function (){
            // 查询当前管理员所有客户
            Route::get('', 'api/v1.Customer/getCustomers');
            // 查询公域池客户
            Route::get('public/all', 'api/v1.Customer/getPublicCustomers');
            // 设置公域池客户归属
            Route::put('public/set', 'api/v1.Customer/setGetCommonCustomer');
            // 释放进入公域池
            Route::put('public/release', 'api/v1.Customer/releaseCustomers');
            // 查询所有客户
            Route::get('all', 'api/v1.Customer/getAllCustomer');
            // 新建客户
            Route::post('', 'api/v1.Customer/create');
            // 查询指定id的客户,并获取审核权限
            Route::get(':id', 'api/v1.Customer/getCustomer', ['id'=>'\d']);
            // 查询指定id的客户
//            Route::get(':id/detail', 'api/v1.Customer/getStatusCustomer', ['id'=>'\d']);
            // 创建，更新跟进
            Route::put('follow', 'api/v1.Customer/followUpdate');
            // 创建，更新跟进
            Route::put('main', 'api/v1.Customer/MainUpdate');
            // 更新客户
            Route::put(':id', 'api/v1.Customer/update', ['id'=>'\d']);
            // 删除客户
            Route::delete('', 'api/v1.Customer/delete');
            // 根据link_code获取单个客户信息
            Route::get('link_code/:link_code', 'api/v1.Customer/getCustomerByLinkcode', ['link_code'=>'\d']);
        });
        Route::group('customer_log',function (){
            // 查询当前管理员所有客户日志
            Route::get('', 'api/v1.CustomerLog/getCustomerLogs');
            // 查询所有客户日志
            Route::get('all', 'api/v1.CustomerLog/getAllCustomerLogs');
            // 新建客户日志
            Route::post('', 'api/v1.CustomerLog/create');
            // 查询指定id的客户日志,并获取审核权限
            Route::get(':id', 'api/v1.CustomerLog/getCustomer', ['id'=>'\d']);
            // 更新客户日志
            Route::put(':id', 'api/v1.CustomerLog/update', ['id'=>'\d']);
            // 删除客户日志
            Route::delete('', 'api/v1.CustomerLog/delete');
        });
        Route::group('customer_project',function (){
            // 查询指定项目信息
            Route::get('', 'api/v1.CustomerProject/getCustomerProjects');
            // 查询所有项目信息
            Route::get('all', 'api/v1.CustomerProject/getAllCustomerProjects');
            // 查询指定id的项目信息
            Route::get(':id', 'api/v1.CustomerProject/getCustomerProject', ['id'=>'\d']);
            // 新建项目信息
            Route::post('', 'api/v1.CustomerProject/create');
            // 更新项目信息
            Route::put(':id', 'api/v1.CustomerProject/update', ['id'=>'\d']);
            // 删除项目信息
            Route::delete('', 'api/v1.CustomerProject/delete');
        });
        Route::group('project_examine',function (){
            // 查询指定项目审核
            Route::get('', 'api/v1.ProjectExamine/getCurUserInfos');
            // 查询所有项目审核
            Route::get('all', 'api/v1.ProjectExamine/getAllInfo');
            // 查询指定id的项目审核
            Route::get(':id', 'api/v1.ProjectExamine/getProjectExamine', ['id'=>'\d']);
            // 新建项目审核
            Route::post('', 'api/v1.ProjectExamine/create');
            // 更新项目审核
            Route::put(':id', 'api/v1.ProjectExamine/update', ['id'=>'\d']);
            // 删除项目信息
//            Route::delete('', 'api/v1.ProjectExamine/delete');
        });
        Route::group('customer_report',function (){
            // 查询当前管理员所有客户
            Route::get('', 'api/v1.CustomerReport/getlists');
            // 查询所有客户
            Route::get('all', 'api/v1.CustomerReport/getAll');
            // 新建客户
            Route::post('', 'api/v1.CustomerReport/create');
            // 查询指定id的客户,并获取审核权限
            Route::get(':id', 'api/v1.CustomerReport/getDetail', ['id'=>'\d']);
            // 查询指定id的客户
            Route::get(':id/detail', 'api/v1.CustomerReport/getStatusDetail', ['id'=>'\d']);
            // 更新客户
            Route::put(':id', 'api/v1.CustomerReport/update', ['id'=>'\d']);
            // 删除客户
            Route::delete('', 'api/v1.CustomerReport/delete');
        });
        Route::group('type', function (){
            // 查询所有类型信息
            Route::get('', 'api/v1.Type/getTypes');
            // 通过字段获取类型值
            Route::get('field', 'api/v1.Type/getFieldValue');
            // 查询指定id的类型信息
            Route::get(':id', 'api/v1.Type/getType', ['id'=>'\d']);
            // 新建类型信息
            Route::post('', 'api/v1.Type/create');
            // 更新类型信息
            Route::put(':id', 'api/v1.Type/update');
            // 删除类型信息
            Route::delete('', 'api/v1.Type/delType');

        });
        Route::group('spec', function (){
            // 查询所有轮播图信息
            Route::get('', 'api/v1.Spec/getSpecs');
            // 查询指定id的轮播图信息
            Route::get(':id', 'api/v1.Spec/getSpec');
            // 新建轮播图信息
            Route::post('', 'api/v1.Spec/create');
            // 更新轮播图信息
            Route::put(':id', 'api/v1.Spec/update');
            // 删除轮播图信息
            Route::delete('', 'api/v1.Spec/delSpec');
            // 获取轮播图元素信息
            Route::get('item/:id', 'api/v1.Spec/getItem');
            // 新建轮播图元素信息
            Route::post('item', 'api/v1.Spec/itemCreate');
            // 更新轮播图元素信息
            Route::put('item/:id', 'api/v1.Spec/itemUpdate');
            // 删除轮播图元素信息
            Route::delete('item', 'api/v1.Spec/delItem');
        });
        Route::group('excel',function (){
            // 查询当前管理员所有客户
            Route::post('customer_log', 'api/v1.ExcelCustomer/importCustomerLog');
            Route::get('customer_log', 'api/v1.ExcelCustomer/exportCustomerLog');
            Route::post('customer', 'api/v1.ExcelCustomer/importCustomer');
            Route::get('customer', 'api/v1.ExcelCustomer/exportCustomer');
        });
        // 数据分析接口
        Route::group('analysis', function () {
            // 时间范围统计订单数据
            Route::get('customer/channel', 'api/v1.Statistics/getCustomerChannelData');
            // 时间范围统计新增会员数
            Route::get('customer/base', 'api/v1.Statistics/getCustomerBaseStatistics');
            // 未跟进客户数统计
            Route::get('customer/no_follow', 'api/v1.Statistics/getCustomerNoFollow');
            // 跟进客户数统计
            Route::get('customer/follow', 'api/v1.Statistics/getCustomerFollowByDate');


        });
    });
})->middleware(['Auth','ReflexValidate'])->allowCrossDomain();

