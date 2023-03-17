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
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequestMapping extends AbstractAnnotationAttribute
{
    /**
     * @param string $path 路由路径 使用"/"开始则忽略控制器分组路径
     * @param string|array $methods 请求方法 例：GET 或 ['GET', 'POST']，默认为所有方法
     * @param string $name 路由名称 用于生成url的别名
     * @param array $params 路由参数
     */
    public function __construct(
        public string       $path = '',
        public string|array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'],
        public string       $name = '',
        public array        $params = [],
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
