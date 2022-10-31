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

## 配置

> 配置文件路径：/plugin/linfly/annotation/annotation.php

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

## 注解验证器

> 验证器注解需要配合验证器扩展包使用，目前只支持 think-validate
> 验证器验证成功会继续执行，验证失败则调用配置的 fail_handle 方法终止执行

```php
use app\validate\UserValidate;
use LinFly\Annotation\Validate\Validate;
use support\Request;
use support\Response;

class IndexController
{
    /**
     * @access public
     * 
     * 注解验证器参数说明 
     * @param string|array $params 验证器参数 支持多个，例如: {"$get.id", "$post.name", "$post.title"}
     * 验证器参数 支持：
     * $post 获取所有 POST 参数
     * $get 获取所有 GET 参数
     * $all 获取所有 REQUEST 参数
     * $post.xx 自定义 POST 参数名称 xx为实际的参数名称
     * $get.xx 自定义 GET 参数名称 xx为实际的参数名称
     * xx 自定义 REQUEST 参数名称 xx为实际的参数名称
     * @param string $validate 验证器类名
     * @param string $scene 验证场景
     * 
     *  
     * @Validate(validate=UserValidate::class)
     * @param Request $request
     * @return Response
     */
    // PHP8注解方式
    //#[Validate(validate: UserValidate::class)]
    public function index(Request $request)
    {
        return response('hello webman');
    }
}
```

## 自定义注解类

### 第一步：创建注解类

> 注解类需要继承 `LinFly\Annotation\Annotation\Annotation` 类
>
> 注解类需要声明 `@Annotation`、`@Target`、`#[\Attribute()]` 注解

测试代码：[annotation-demo](https://github.com/imlinfly/webman-annotation/raw/master/demo/annotation-demo.zip)

#### 控制器注解类

```php
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TestControllerParams extends AbstractAnnotation
{
    /**
     * 第一个参数必须包含array类型，用来接收注解的参数
     * @param string|array $controller
     */
    public function __construct(public string|array $controller = '')
    {
        $this->paresArgs(func_get_args(), 'name');
    }
}
```

#### 方法注解类

```php
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class TestMethodParams extends AbstractAnnotation
{
    /**
     * 第一个参数必须包含array类型，用来接收注解的参数
     * @param string|array $method
     */
    public function __construct(public string|array $method = '')
    {
        $this->paresArgs(func_get_args(), 'method');
    }
}
```

### 第二步：使用注解类

```php
use app\annotation\params\TestControllerParams;
use app\annotation\params\TestMethodParams;
use support\Request;

/**
 * 自定义类的注解类
 * @TestControllerParams(controller=IndexController::class)
 */
// PHP8原生注解
// #[TestControllerParams(controller: IndexController::class)]
class IndexController
{
    /**
     * 自定义方法的注解类
     * @TestMethodParams(method="index")
     */
    // PHP8原生注解
    // #[TestMethodParams(method: __METHOD__)]
    public function index(Request $request)
    {
        // 业务代码 ...
    }
}
```

### 第三步：获取注解类的参数

#### 方法一：通过 LinFly\Annotation\Annotation\Annotation::yieldParseClassAnnotations() 方法获取

```php
use app\annotation\params\TestControllerParams;
use app\annotation\params\TestMethodParams;
use LinFly\Annotation\Annotation;

// 获取指定类的注解列表 包括：类注解、属性注解、方法注解、方法参数注解
$generator = Annotation::yieldParseClassAnnotations(IndexController::class);
/**
 * @var string $annotationName 注解类类名
 * @var array $items 注解类参数列表
 */
foreach ($generator as $annotationName => $items) {
    switch ($annotationName) {
        case TestControllerParams::class:
        case TestMethodParams::class:
            foreach ($items as $item) {
                var_dump($item['type'] . ' -> ' . $item['annotation']);
            }
            break;
    }
}
```

#### 方法二：通过 AnnotationHandle 获取

程序启动时会扫描所有的注解类，扫描完成后会调用已绑定注解类的回调事件

1. 新建Handle类
   注意：Handle类需要继承 `LinFly\Annotation\Handle\Handle` 类

```php
namespace app\annotation\handle;

use LinFly\Annotation\Interfaces\IAnnotationHandle;

class TestHandle implements IAnnotationHandle
{
    public static function handle(array $item): void
    {
        switch ($item['type']) {
            case 'class':
            case 'method':
                var_dump($item['type'] . ' -> ' . $item['annotation']);
                break;
        }
    }
}
```

2. 新建Bootstrap类
   参考资料：[webman业务初始化](https://www.workerman.net/doc/webman/others/bootstrap.html)

```php
namespace app\bootstrap;

use app\annotation\handle\TestHandle;
use app\annotation\params\TestControllerParams;
use app\annotation\params\TestMethodParams;
use LinFly\Annotation\Annotation;
use Webman\Bootstrap;

class CreateAnnotationHandle implements Bootstrap
{
    /**
     * start
     * @access public
     * @param $worker
     * @return void
     */
    public static function start($worker)
    {
        // monitor进程不执行
        if ($worker?->name === 'monitor') {
            return;
        }

        // 添加测试控制器注解类处理器
        Annotation::addHandle(TestControllerParams::class, TestHandle::class);
        // 添加测试控制器方法注解类处理器
        Annotation::addHandle(TestMethodParams::class, TestHandle::class);
    }
}
```

3. 配置随进程启动

打开`config/bootstrap.php`将`CreateAnnotationHandle`类加入到启动项中。

```php
return [
    // ...这里省略了其它配置...
    
    app\bootstrap\CreateAnnotationHandle::class,
];
```
