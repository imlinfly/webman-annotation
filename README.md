# webman annotation 注解插件

> 本插件使用 [doctrine/annotation](https://github.com/doctrine/annotations) 对注释解析
>
> PHP版本要求 >= 8.0
>
> 支持注释注解 和 PHP8注解方式

## 安装

```shell
composer require linfly/annotation
```

## 使用

### 注解路由

```php
use LinFly\Annotation\Route\Controller;
use LinFly\Annotation\Route\Route;

/**
 * 控制器注解
 * @param string|array $prefix 路由分组路径
 * 
 * @Controller(prefix="/api")
 */
// PHP8注解方式
#[Controller(prefix: '/api')]
class ApiController
{
    /**
     * 注解路由
     * @param string|array $path 路由路径 使用"/"开始则忽略控制器分组路径
     * @param array $methods 请求方法 例：GET 或 ['GET', 'POST']，默认为所有方法
     * @param string $name 路由名称 用于生成url的别名
     * @param array $params 路由参数
     * 
     * @Route(path="get", methods="get")
     */
     // PHP8注解方式
     #[Route(path: 'get', methods: 'get')]
     public function get()
     {
         return 'get';
     }
}
```

### 注解中间件

```php
use LinFly\Annotation\Route\Controller;
use LinFly\Annotation\Route\Middleware;
use LinFly\Annotation\Route\Route;
/**
 * @Controller(prefix="/api")
 */
class ApiController
{
    /**
     * 注解中间件 需要和注解路由一起使用
     * @param string|array $middlewares 路由中间件 支持多个中间件
     * 
     * @Route(path="get", methods="get")
     * 
     * @Middleware(middlewares=AuthMiddleware::class)
     * @Middleware(middlewares={AuthMiddleware::class, TokenCheckMiddleware::class})
     */
     // PHP8注解方式
     #[Middleware(middlewares=AuthMiddleware::class)]
     #[Middleware(middlewares=[AuthMiddleware::class, TokenCheckMiddleware::class])]
     public function get()
     {
         return 'get';
     }
}
```

## 配置文件

> 文件路径：/plugin/linfly/annotation/annotation.php

```php
<?php

return [
    // 注解扫描路径
    'include_paths' => [
        // 应用目录
        'app',
    ],
    // 扫描排除的路径 支持通配符: *
    'exclude_paths' => [
        'app/model',
    ],
    // 路由设置
    'route' => [
        // 如果注解路由 @Route() 未传参则默认使用方法名作为path
        'use_default_method' => false,
    ],
];

```
