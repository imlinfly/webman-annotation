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
#[Attribute(Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Controller extends AbstractAnnotationAttribute
{
    /**
     * @param string $prefix 路由分组路径
     */
    public function __construct(public string $prefix = '')
    {
        // 解析参数
        $this->setArguments(func_get_args());
    }

    public static function getParser(): string
    {
        return RouteAnnotationParser::class;
    }
}
