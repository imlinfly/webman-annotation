<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/01/13 16:24:25
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
#[Attribute(Attribute::TARGET_METHOD)]
class BindRoute extends AbstractAnnotationAttribute
{
    /**
     * @param array $params 路由参数
     * @param string $name 路由名称 用于生成url的别名
     */
    public function __construct(
        public array  $params = [],
        public string $name = '',
    )
    {
        // 解析参数
        $this->setArguments(func_get_args());
    }

    public static function getParser(): string
    {
        return RouteAnnotationParser::class;
    }
}
