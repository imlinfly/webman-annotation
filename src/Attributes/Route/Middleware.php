<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:08:45
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Attributes\Route;

use Attribute;
use LinFly\Annotation\AbstractAnnotationAttribute;
use LinFly\Annotation\Parser\RouteAnnotationParser;

/**
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Middleware extends AbstractAnnotationAttribute
{
    /**
     * 注解中间件
     * @param string $middlewares 路由中间件 支持多个中间件
     * @param array $only 指定需要走中间件的方法, 不指定则全部走中间件, 与except互斥 只支持在控制器中使用
     * @param array $except 指定不需要走中间件的方法, 不指定则全部走中间件, 与only互斥 只支持在控制器中使用
     */
    public function __construct(public string $middlewares, public array $only = [], public array $except = [])
    {
        $this->setArguments(func_get_args());
    }

    public static function getParser(): string
    {
        return RouteAnnotationParser::class;
    }
}
