<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:08:45
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS", "METHOD")
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Inherit extends AbstractAnnotation
{
    /**
     * @param array|false $only
     * @param array $except
     * @param bool $merge
     */
    public function __construct(public array|false $only = [], public array $except = [], public bool $merge = true)
    {
        // 解析参数
        $this->paresArgs(func_get_args(), 'only');
    }
}
