<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/11 11:05:35
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Handle;

use LinFly\Annotation\Interfaces\IAnnotationHandle;
use LinFly\Annotation\Route\Controller;
use LinFly\Annotation\Route\Middleware;
use LinFly\Annotation\Route\Route;
use Webman\Route as WebManRoute;

abstract class RouteAnnotationHandle implements IAnnotationHandle
{
    /**
     * 控制器注解
     * @var array
     */
    protected static array $controllers = [];

    /**
     * 控制器中间件
     * @var array
     */
    protected static array $middlewares = [];

    /**
     * 保存的路由
     * @var array
     */
    protected static array $routes = [];

    /**
     * 处理路由注解
     * @access public
     * @param array $item
     * @return void
     */
    public static function handle(array $item): void
    {
        if ($item['type'] === 'class') {
            self::handleClassAnnotation($item, $item['class']);
        } else if ($item['type'] === 'method') {
            self::handleMethodAnnotation($item, $item['class']);
        }
    }

    /**
     * 处理类注解
     * @access public
     * @param array $item
     * @param string $className
     * @return void
     */
    public static function handleClassAnnotation(array $item, string $className)
    {
        $annotation = $item['annotation'];
        $parameters = $item['parameters'];

        switch ($annotation) {
            // 控制器注解
            case  Controller::class:
                static::$controllers[$className] ??= [];
                static::$controllers[$className][] = $parameters;
                break;

            // 控制器中间件注解
            case Middleware::class:
                $middlewares = static::$middlewares[$className] ??= [];
                static::$middlewares[$className] = array_merge($middlewares, (array)$parameters['middlewares']);
                break;
        }
    }

    /**
     * 处理方法注解
     * @access public
     * @param array $item
     * @param string $className
     * @return void
     */
    public static function handleMethodAnnotation(array $item, string $className)
    {
        $method = $item['method'];
        $annotation = $item['annotation'];
        $parameters = $item['parameters'];

        switch ($annotation) {
            // 路由注解
            case Route::class:
                static::$routes[] = $item;
                break;

            // 方法中间件注解
            case Middleware::class:
                $middlewares = static::$middlewares[$className . ':' . $method] ??= [];
                static::$middlewares[$className . ':' . $method] = array_merge($middlewares, (array)$parameters['middlewares']);
                break;
        }
    }

    /**
     * 创建路由
     * @access public
     * @param bool $isClear 是否清除路由
     * @return void
     */
    public static function createRoute(bool $isClear = true)
    {
        $useDefaultMethod = config('plugin.linfly.annotation.annotation.route.use_default_method', true);

        foreach (self::$routes as $item) {
            $parameters = $item['parameters'];

            if (!isset($item['arguments']['path']) && $useDefaultMethod) {
                $parameters['path'] = $item['method'];
            }

            // 忽略控制器注解的path参数
            if (str_starts_with($parameters['path'], '/')) {
                // 添加路由
                self::addRoute($parameters['path'], $item);
                continue;
            }

            foreach (self::$controllers[$item['class']] ?? [['prefix' => '']] as $controller) {
                // 控制器注解的path参数
                $controllerPath = trim($controller['prefix'] ?? '', '/');
                // 路由地址
                $path = ($controllerPath ? '/' . $controllerPath : '') . ($parameters['path'] ? '/' . $parameters['path'] : '');
                // 添加路由
                self::addRoute($path, $item);
            }
        }

        if ($isClear) {
            // 资源回收
            self::recovery();
        }
    }

    /**
     * 添加路由
     * @access public
     * @param string $path
     * @param array $item
     * @return void
     */
    protected static function addRoute(string $path, array $item)
    {
        $parameters = $item['parameters'];

        // 添加路由
        $route = WebManRoute::add($parameters['methods'], ($path ?: '/'), [$item['class'], $item['method']]);
        // 路由参数
        $parameters['params'] && $route->setParams($parameters['params']);
        // 路由名称
        $parameters['name'] && $route->name($parameters['name']);
        // 控制器中间件
        $route->middleware(self::$middlewares[$item['class']] ?? null);
        // 方法中间件
        $route->middleware(self::$middlewares[$item['class'] . ':' . $item['method']] ?? null);
    }

    /**
     * 资源回收
     * @access public
     * @return void
     */
    protected static function recovery()
    {
        // 清空控制器注解
        self::$controllers = [];
        // 清空控制器中间件注解
        self::$middlewares = [];
    }
}
