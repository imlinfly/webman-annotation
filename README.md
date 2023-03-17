# webman annotation 注解插件

> 本插件使用 [doctrine/annotation](https://github.com/doctrine/annotations) 对注释解析
>
> PHP版本要求 >= 8.0
>
> 支持注释注解 和 PHP8注解方式

### 目前支持的功能

* 路由注解
* 中间件注解
* 验证器注解
* 自定义注解
* 注解继承 `v1.0.5` 版本开始支持
* 依赖注入 `v1.0.7` 版本开始支持

## 安装

```shell
composer require linfly/annotation:^1.0
```

## 配置

> 配置文件路径：/plugin/linfly/annotation/annotation.php

```php
<?php

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

```

## 使用

### 注解路由

> 支持的注解方式：
> `@Route` `@GetRoute` `@PostRoute` `@PutRoute` `@DeleteRoute`
> `@PatchRoute` `@HeadRoute` `@OptionsRoute`
>
> 除`@Route`注解外，其他注解都是v1.0.5版本开始支持
>
> 除`@Route`注解外，其他注解都是`@Route`的别名，使用方式一致
>

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
 * 
 * 
 * 注解中间件 需要和注解路由一起使用
 * @param string|array $middlewares 路由中间件 支持多个中间件
 * @param array $only 指定需要走中间件的方法, 不指定则全部走中间件, 与except互斥 只支持在控制器上使用
 * @param array $except 指定不需要走中间件的方法, 不指定则全部走中间件, 与only互斥 只支持在控制器上使用
 * 
 * @Middleware(middlewares=AuthMiddleware::class, only={"get"})
 */
// PHP8注解方式
#[Middleware(middlewares=AuthMiddleware::class, only: ['get'])]
class ApiController
{
    /**
     * @Route(path="get", methods="get")
     * @Middleware(middlewares={TokenCheckMiddleware::class})
     */
     // PHP8注解方式
     #[Middleware(middlewares=[TokenCheckMiddleware::class])]
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

### 绑定原生路由

> 绑定Webman原生路由后，在不使用注解路由的情况下，也能使用注解中间件、验证器等功能
>
> 注解类 `@BindRoute`
>
> 绑定原生路由 `v1.0.7` 版本开始支持

```php
use LinFly\Annotation\Route\BindRoute;
use LinFly\Annotation\Route\Middleware;
use LinFly\Annotation\Validate\Validate;
use support\Request;
use support\Response;
use app\validate\UserValidate;

class IndexController
{
    /**
    * 绑定原生路由注解参数说明
    * @param array $params 路由参数
    * @param string $name 路由名称 用于生成url的别名
    */
    #[BindRoute(params: ['info' => 'test_bind_route'], name: 'test_bind_route')]
    #[Validate(validate: UserValidate::class)]
    #[Middleware(middlewares=[TokenCheckMiddleware::class])]
    public function index(Request $request)
    {
        $name = $request->route->getName();
        $params = $request->route->param();
        return response('hello webman');
    }
}
```

## 注解继承

> 注解继承可以让你的注解更加简洁，减少重复代码

### 参数说明

```php
/**
 * 指定需要继承的方法, 不指定则全部继承, 与except互斥; 如果为false, 则不继承任何注解
 * @param array|false $only
 * 指定不需要继承的方法, 不指定则全部继承, 与only互斥
 * @param array $except
 * 参数为true时, 则合并父类的注解, 参数为false时, 则覆盖父类的注解
 * @param bool $merge
 */
public function __construct(public array|false $only = [], public array $except = [], public bool $merge = true);
```

### 使用例子

父类：UserAuthController.php

```php
<?php

namespace app\controller;

use app\middleware\UserAuthMiddleware;
use app\validate\UserValidate;
use LinFly\Annotation\Annotation\Inherit;
use LinFly\Annotation\Route\Controller;
use LinFly\Annotation\Route\Middleware;
use LinFly\Annotation\Route\Route;
use LinFly\Annotation\Validate\Validate;
use support\Request;

#[
    // 用户授权中间件
    Middleware(middlewares: UserAuthMiddleware::class),
    Validate(validate: UserValidate::class),
    // 让所有子类都继承父类的注解（父类使用了继承注解, 子类可选使用继承注解）
    Inherit,
    // 让所有子类只继承父类的中间件注解
    // Inherit(only: [Middleware::class]),
]
abstract class UserAuthController
{

}
```

子类：TestController.php

```php
<?php

namespace app\controller;

use LinFly\Annotation\Route\Controller;
use LinFly\Annotation\Route\GetRoute;

#[
    Controller(prefix: 'user'),
    // 不继承父类的注解
    // Inherit(only: false),
    // 继承父类的所有注解
    // Inherit(only: []),
    // 只继承父类的中间件注解
    // Inherit(only: [Middleware::class]),
    // 不继承父类的验证器注解
    // Inherit(except: [Validate::class]),
]
class TestController extends UserAuthController
{
    #[GetRoute]
    public function info()
    {
        return json([
            'user_id' => 1,
            'username' => 'test',
        ]);
    }
}
```

### 根据命名空间注解

> 根据命名空间的名称自动设置控制器路径，配合"注解继承"来使用更方便。
>
> 注解类 `@NamespaceController`
>
> 绑定原生路由 `v1.0.7` 版本开始支持

### 参数说明

```php
/**
 * @param string|array $path
 * 自定义控制器路径 变量{$className}值为删除命名空间前缀后转小驼峰的名称
 * 
 * @param string $namespace
 * 需要删除的命名空间前缀
 * 
 * @param null|callable $filter
 * 自定义过滤器
 */
public function __construct(string|array $path = '/{$className}', public string $namespace = '', ?callable $filter = null);
```

### 使用例子

```php
use LinFly\Annotation\Route\NamespaceController;
use LinFly\Annotation\Route\GetRoute;

// 自动设置控制器路径，通过注解继承来使用则只需要在父类上使用命名空间注解即可
#[NamespaceController(namespace: 'app\controller')]
class IndexController
{
    #[GetRoute]
    public function index()
    {
        return 'hello webman';
    }
}
```

## 依赖注入

### 配置

修改`config/container.php`文件，修改后内容如下：

```php
$container = \LinFly\FacadeContainer::getInstance();
$container->definition(config('dependence', []));
return $container;
```

### 参数说明

```php
/**
 * @param string|array $name 实例或者别名
 * @param array $parameters 参数
 */
public function __construct(protected string $name = '', protected array $parameters = []);
```

### 使用例子

需要注入的类：TestService.php

```php
<?php

namespace app\service;

use LinFly\Annotation\Annotation\Inject;

class TestService
{
    public function test()
    {
        return true;
    }
}
```

调用类：TestController.php

```php
<?php

namespace app\controller;

use LinFly\Annotation\Annotation\Inject;
use app\service\TestService;

class TestController
{
    /**
     * @Inject()
     * 
     * 可以使用var注解指定注入的类, 或者声明属性类型来指定注入的类
     * 更推荐使用声明属性类型的方式，优先级高于var注解
     * @var TestService
     */
    // PHP8注解方式
    #[Inject]
    protected TestService $testService;

    public function test() {
        var_dump($this->testService->test());
        // true
    }
}
```

### 实验性功能

#### 1. 循环依赖

依赖注入支持循环依赖，即A依赖B，B依赖A，但是需要注意的是，依赖注入的类必须是单例模式，否则会报错。

构造函数支持注入自身实例，注入自身的实例是单例实例，不会重复创建。

## 自定义注解类

### 第一步：创建注解类

> 注解类需要继承 `LinFly\Annotation\Annotation\Annotation` 类
>
> 注解类需要声明 `@Annotation`、`@Target`、`#[\Attribute()]` 注解

测试代码：[annotation-example](https://github.com/imlinfly/webman-annotation/raw/master/example/annotation-example.zip)

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
        $this->paresArgs(func_get_args(), 'controller');
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

## 更新日志

### v1.0.9

1. 新增根据命名空间自动设置控制器路径的注解

### v1.0.8

1. 新增绑定原生路由注解支持绑定路由参数和路由命名

### v1.0.7

1. 新增依赖注入功能
2. 新增绑定原生路由注解

### v1.0.6

1. 修复部署模式调用STDOUT报错。

### v1.0.5

1. 修正注解路由请求方法大小写问题。
2. 新增注解继承功能。
3. 新增 `@GetRoute` `@PostRoute` 等注解路由类。
4. 新增 `include_paths` 注解扫描路径参数支持通配符。
5. 新增支持扫描单个文件中写入多个类的文件。

### v1.0.4

1. 修复注解路由可选参数解析问题。

### v1.0.3

1. 修复中间件注解和验证器注解执行顺序问题。
2. 新增中间件only和except参数, 用于指定中间件只在指定的方法执行。
3. 新增在控制器上使用验证器注解。
