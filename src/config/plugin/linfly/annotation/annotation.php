<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:57:15
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

return [
    // 注解扫描路径
    'include_paths' => [
        // 应用目录 支持通配符: * , 例如: app/*, app/*.php
        'app',
    ],
    // 扫描排除的路径 支持通配符: *
    'exclude_paths' => [
        'app/model',
    ],
    // 路由设置
    'route' => [
        // 如果注解路由 @Route() 未传参则默认使用方法名作为path
        'use_default_method' => true,
    ],
    // 验证器注解
    'validate' => [
        // 验证器验证处理类 (该功能需要自行安装对应的验证器扩展包)，目前只支持 think-validate
        'handle' => LinFly\Annotation\Validate\Handle\ThinkValidate::class,
        // 验证失败处理方法
        'fail_handle' => function (Webman\Http\Request $request, string $message) {
            return json(['code' => 500, 'msg' => $message]);
        }
    ],
];
