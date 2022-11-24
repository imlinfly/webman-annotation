<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/16 16:11:29
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Bootstrap;

use LinFly\Annotation\Handle\RouteAnnotationHandle;
use LinFly\Annotation\Handle\ValidateAnnotationHandle;
use LinFly\Annotation\Route\Controller;
use LinFly\Annotation\Route\Middleware;
use LinFly\Annotation\Route\Route;
use LinFly\Annotation\Validate\Validate;
use Webman\Bootstrap;
use LinFly\Annotation\Annotation;

class AnnotationBootstrap implements Bootstrap
{
    protected static array $defaultConfig = [
        'include_paths' => [
            'app',
        ],
        'exclude_paths' => [],
    ];

    public static array $config = [];

    public static function start($worker)
    {
        // monitor进程不执行
        if ($worker?->name == 'monitor') {
            return;
        }

        // 获取配置
        self::$config = config('plugin.linfly.annotation.annotation', []);
        $config = self::$config = array_merge(self::$defaultConfig, self::$config);

        self::createAnnotationHandle();

        // 注解扫描
        $generator = Annotation::scan($config['include_paths'], $config['exclude_paths']);
        // 解析注解
        Annotation::parseAnnotations($generator);
    }

    protected static function createAnnotationHandle()
    {
        // 控制器注解
        Annotation::addHandle(Controller::class, RouteAnnotationHandle::class);
        // 路由注解
        Annotation::addHandle(Route::class, RouteAnnotationHandle::class);
        // 中间件注解
        Annotation::addHandle(Middleware::class, RouteAnnotationHandle::class);
        // 验证器注解
        Annotation::addHandle(Validate::class, RouteAnnotationHandle::class);
    }
}
