<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/11 11:05:35
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Parser;

use LinFly\Annotation\Attributes\Route\DeleteMapping;
use LinFly\Annotation\Attributes\Route\RequestMapping;
use LinFly\Annotation\Bootstrap\AnnotationBootstrap;
use LinFly\Annotation\Contracts\IAnnotationParser;
use LinFly\Annotation\Attributes\Route\BindRoute;
use LinFly\Annotation\Attributes\Route\Controller;
use LinFly\Annotation\Attributes\Route\GetMapping;
use LinFly\Annotation\Attributes\Route\HeadMapping;
use LinFly\Annotation\Attributes\Route\Middleware;
use LinFly\Annotation\Attributes\Route\NamespaceController;
use LinFly\Annotation\Attributes\Route\OptionsMapping;
use LinFly\Annotation\Attributes\Route\PatchMapping;
use LinFly\Annotation\Attributes\Route\PostMapping;
use LinFly\Annotation\Attributes\Route\PutMapping;
use LinFly\Annotation\Validate\ValidateMiddleware;
use Webman\Route as WebManRoute;
use Webman\Route\Route as RouteObject;

abstract class RouteAnnotationParser implements IAnnotationParser
{
    /**
     * 控制器注解
     * @var array
     */
    protected static array $controllers = [];

    /**
     * 命名空间控制器注解
     * @var array
     */
    protected static array $namespaceControllers = [];

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
     * 和原生路由绑定的注解路由列表
     * @var array<array<string>>
     */
    protected static array $bindRoutes = [];

    /**
     * 处理路由注解
     * @access public
     * @param array $item
     * @return void
     */
    public static function process(array $item): void
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
    public static function handleClassAnnotation(array $item, string $className): void
    {
        $annotation = $item['annotation'];
        $parameters = $item['parameters'];

        switch ($annotation) {
            // 控制器注解
            case  Controller::class:
                static::$controllers[$className] ??= [];
                static::$controllers[$className][] = $parameters;
                break;

            // 命名空间控制器注解
            case  NamespaceController::class:
                static::$namespaceControllers[$className] = [
                    'class' => $item['class'],
                    'namespace' => $parameters['namespace'],
                    'filter' => $parameters['filter'] ?? [NamespaceController::class, 'camel'],
                    'path' => $parameters['path'],
                ];
                break;

            // 控制器中间件注解
            case Middleware::class:
                static::$middlewares[$className] ??= [];
                static::$middlewares[$className][] = [
                    'middlewares' => (array)$parameters['middlewares'],
                    'only' => $parameters['only'],
                    'except' => $parameters['except'],
                ];
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
    public static function handleMethodAnnotation(array $item, string $className): void
    {
        $method = $item['method'];
        $annotation = $item['annotation'];
        $parameters = $item['parameters'];

        switch ($annotation) {
            // 路由注解
            case RequestMapping::class:
            case GetMapping::class:
            case PostMapping::class:
            case HeadMapping::class:
            case PatchMapping::class:
            case OptionsMapping::class:
            case PutMapping::class:
            case DeleteMapping::class:
                static::$routes[] = $item;
                break;

            // 绑定原生路由注解
            case BindRoute::class:
                $key = $item['class'] . '@' . $item['method'];
                static::$bindRoutes[$key] = $item;
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
    public static function createRoute(bool $isClear = true): void
    {
        // 绑定原生路由
        self::bindNativeRoute();

        $useDefaultMethod = AnnotationBootstrap::$config['route']['use_default_method'] ?? true;

        foreach (self::$routes as $item) {
            $parameters = $item['parameters'];

            // 未指定path参数且开启默认方法路由, 则使用方法名作为路由
            if (!isset($item['arguments']['path']) && $useDefaultMethod) {
                $parameters['path'] = $item['method'];
            }

            // 忽略控制器注解的path参数
            if (str_starts_with($parameters['path'], '/')) {
                // 添加路由
                self::addRoute($parameters['path'], $item);
                continue;
            }

            // 命名空间后缀
            $controllerSuffix = (string)config('app.controller_suffix');

            foreach (self::$controllers[$item['class']] ?? [['prefix' => '']] as $controller) {

                if ($namespaceController = self::$namespaceControllers[$item['class']] ?? false) {
                    $namespace = $namespaceController['namespace'];
                    $filter = $namespaceController['filter'];
                    $path = $namespaceController['path'];
                    $class = $namespaceController['class'];

                    // 删除控制器后缀
                    if ($controllerSuffix && str_ends_with($class, $controllerSuffix)) {
                        $class = substr($class, 0, -strlen($controllerSuffix));
                    }

                    // 删除命名空间
                    $class = str_replace([$namespace, '\\'], ['', '/'], $class);
                    $class = $filter($class);
                    $controllerPath = str_replace('{$className}', trim($class, '/'), $path);
                } else {
                    $controllerPath = $controller['prefix'] ?? '';
                }


                // 控制器注解的path参数
                $controllerPath = trim($controllerPath, '/');
                // 路由注解的path参数
                $routePath = $parameters['path'];

                // 控制器注解的path参数不为空时，拼接 "/" 路径分隔符，如果path参数以 "[/" (可变参数) 开头，则不拼接
                $controllerPath = $controllerPath ? (str_starts_with($controllerPath, '[/') ? '' : '/') . $controllerPath : '';
                // 路由注解的path参数不为空时，拼接 "/" 路径分隔符，如果path参数以 "[/" (可变参数) 开头，则不拼接
                $routePath = $routePath ? (str_starts_with($routePath, '[/') ? '' : '/') . $routePath : '';

                // 添加路由
                self::addRoute($controllerPath . $routePath, $item);
            }
        }

        if ($isClear) {
            // 资源回收
            self::recovery();
        }
    }

    /**
     * 绑定原生路由
     * @return void
     */
    protected static function bindNativeRoute()
    {
        if (empty(self::$bindRoutes)) {
            return;
        }
        foreach (WebManRoute::getRoutes() as $route) {
            $callback = $route->getCallback();
            if (is_array($callback) && $callback[1]) {
                $key = $callback[0] . '@' . $callback[1];
                $item = static::$bindRoutes[$key] ?? false;
                if ($item) {
                    self::setRouteInfo($route, $item);
                }
            }
        }
    }

    /**
     * 添加路由
     * @access public
     * @param string $path
     * @param array $item
     * @return void
     */
    protected static function addRoute(string $path, array $item): void
    {
        $parameters = $item['parameters'];

        // 路由请求方法转大写
        $parameters['methods'] = array_map('strtoupper', (array)$parameters['methods']);
        // 添加路由
        $route = WebManRoute::add($parameters['methods'], ($path ?: '/'), [$item['class'], $item['method']]);

        self::setRouteInfo($route, $item);
    }

    /**
     * 设置路由信息
     * @param RouteObject $route
     * @param array $item
     * @return void
     */
    protected static function setRouteInfo(RouteObject $route, array $item)
    {
        $parameters = $item['parameters'];

        // 路由参数
        $parameters['params'] && $route->setParams($parameters['params']);
        // 路由名称
        $parameters['name'] && $route->name($parameters['name']);
        // 路由中间件
        self::addMiddleware($route, $item['class'], $item['method']);
    }

    /**
     * 添加中间件
     * @param RouteObject $route
     * @param string $class
     * @param string $method
     * @return void
     */
    protected static function addMiddleware(RouteObject $route, string $class, string $method): void
    {
        // 类中间件
        $classMiddlewares = self::$middlewares[$class] ?? [];
        // 方法中间件
        $methodMiddlewares = self::$middlewares[$class . ':' . $method] ?? [];

        // 添加类中间件
        foreach ($classMiddlewares as $item) {
            // 填写了only参数且不在only参数中则native跳过
            if ($item['only'] && !in_array($method, $item['only'])) {
                continue;
            } // 填写了except参数且在except参数中则跳过
            else if ($item['except'] && in_array($method, $item['except'])) {
                continue;
            }
            $route->middleware($item['middlewares']);
        }

        // 添加方法中间件
        $route->middleware($methodMiddlewares);

        // 如果有验证器注解则添加验证器中间件
        if (ValidateAnnotationParser::isExistValidate($class, $method)) {
            $route->middleware(ValidateMiddleware::class);
        }
    }

    /**
     * 资源回收
     * @access public
     * @return void
     */
    protected static function recovery(): void
    {
        // 清空控制器注解
        self::$controllers = [];
        // 清空控制器中间件注解
        self::$middlewares = [];
        // 清空绑定原生路由注解
        static::$bindRoutes = [];
    }
}
