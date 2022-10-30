<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:08:45
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Route;

use Attribute;
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS", "METHOD")
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Middleware extends AbstractAnnotation
{
    /**
     * 注解中间件
     * @param string|array $middlewares 路由中间件 支持多个
     */
    public function __construct(public string|array $middlewares)
    {
        // 解析参数
        $this->paresArgs(func_get_args(), 'middlewares');
    }
}
