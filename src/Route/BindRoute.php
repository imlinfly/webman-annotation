<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/01/13 16:24:25
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Route;

use Attribute;
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
class BindRoute extends AbstractAnnotation
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
        $this->paresArgs(func_get_args(), 'params');
    }
}
