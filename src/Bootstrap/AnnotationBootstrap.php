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
use LinFly\Annotation\Util\AnnotationUtil;
use LinFly\Annotation\Validate\Validate;
use ReflectionException;
use Webman\Bootstrap;
use LinFly\Annotation\Annotation;

class AnnotationBootstrap implements Bootstrap
{
    protected static array $defaultConfig = [
        'include_paths' => [
            'app',
        ],
        'exclude_paths' => [],
        'route' => [
            'use_default_method' => true,
        ],
    ];

    /**
     * 进程名称
     * @var string
     */
    protected static string $workerName = '';

    /**
     * 注解配置
     * @var array
     */
    public static array $config = [];

    /**
     * @param $worker
     * @return void
     * @throws ReflectionException
     */
    public static function start($worker)
    {
        // 初始化配置
        self::initConfig();

        // 跳过忽略的进程
        if (!$worker || self::isIgnoreProcess(self::$workerName = $worker->name)) {
            return;
        }

        // 注册注解处理类
        self::createAnnotationHandle();

        echo '[' . self::$workerName . '] Start scan annotations...' . PHP_EOL;
        $time = microtime(true);

        // 注解扫描
        $generator = Annotation::scan(self::$config['include_paths'], self::$config['exclude_paths']);
        // 解析注解
        Annotation::parseAnnotations($generator);

        $time = round(microtime(true) - $time, 2);
        echo '[' . self::$workerName . '] Scan annotations completed, time: ' . $time . 's' . PHP_EOL;
    }

    /**
     * 设置注解处理回调
     * @return void
     */
    protected static function createAnnotationHandle(): void
    {
        // 控制器注解
        Annotation::addHandle(Controller::class, RouteAnnotationHandle::class);
        // 路由注解
        Annotation::addHandle(Route::class, RouteAnnotationHandle::class);
        // 中间件注解
        Annotation::addHandle(Middleware::class, RouteAnnotationHandle::class);
        // 验证器注解
        Annotation::addHandle(Validate::class, ValidateAnnotationHandle::class);
    }

    /**
     * 初始化配置
     * @return array
     */
    protected static function initConfig(): array
    {
        // 获取配置
        self::$config = config('plugin.linfly.annotation.annotation', []);
        self::$config = array_merge(self::$defaultConfig, self::$config);

        // include_paths 转正则表达式
        $regex = '';
        foreach (self::$config['include_paths'] as $path) {
            $path = AnnotationUtil::basePath(AnnotationUtil::replaceSeparator($path));
            $regex .= preg_quote($path) . '|';
        }
        self::$config['include_regex_paths'] = '/^(' . rtrim($regex, '|') . ')/';

        return self::$config;
    }

    /**
     * 是否为忽略的进程
     * @param string|null $name
     * @return bool
     */
    public static function isIgnoreProcess(string $name = null): bool
    {
        if (empty($name)) {
            $name = self::$workerName;
        }

        return in_array($name, [
            '',
            'monitor',
        ]);
    }

    /**
     * 获取进程名称
     * @return string
     */
    public static function getWorkerName(): string
    {
        return self::$workerName;
    }
}
