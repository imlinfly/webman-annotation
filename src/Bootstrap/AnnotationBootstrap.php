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
use LinFly\Annotation\Route\GetRoute;
use LinFly\Annotation\Route\HeadRoute;
use LinFly\Annotation\Route\Middleware;
use LinFly\Annotation\Route\OptionsRoute;
use LinFly\Annotation\Route\PatchRoute;
use LinFly\Annotation\Route\PostRoute;
use LinFly\Annotation\Route\PutRoute;
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
     * 忽略的进程名称
     * @var string[]
     */
    public static array $ignoreProcess = [
        '',
        'monitor',
    ];

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

        echo '[Process:' . self::$workerName . '] Start scan annotations...' . PHP_EOL;
        $time = microtime(true);

        // 注解扫描
        $generator = Annotation::scan(self::$config['include_paths'], self::$config['exclude_paths']);
        // 解析注解
        Annotation::parseAnnotations($generator);

        $time = round(microtime(true) - $time, 2);
        echo '[Process:' . self::$workerName . '] Scan annotations completed, time: ' . $time . 's' . PHP_EOL;
    }

    /**
     * 设置注解处理回调
     * @return void
     */
    protected static function createAnnotationHandle(): void
    {
        // 添加注解处理类
        Annotation::addHandle([
            // 控制器注解
            Controller::class,

            // 路由注解
            Route::class,
            GetRoute::class,
            PostRoute::class,
            HeadRoute::class,
            PatchRoute::class,
            OptionsRoute::class,
            PutRoute::class,

            // 中间件注解
            Middleware::class,
        ], RouteAnnotationHandle::class);

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
        $regex = AnnotationUtil::excludeToRegular(self::$config['include_paths']);
        self::$config['include_regex_paths'] = $regex ? '/^(' . $regex . ')/' : '';

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

        return in_array($name, self::$ignoreProcess);
    }

    /**
     * 获取进程名称
     * @return string
     */
    public static function getWorkerName(): string
    {
        return self::$workerName;
    }

    /**
     * 设置忽略的进程名称
     * @param array $ignoreProcess
     * @param bool $isClear
     */
    public static function setIgnoreProcess(array $ignoreProcess, bool $isClear): void
    {
        if ($isClear) {
            self::$ignoreProcess = $ignoreProcess;
        } else {
            self::$ignoreProcess = array_merge(self::$ignoreProcess, $ignoreProcess);
        }
    }
}
