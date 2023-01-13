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
    public function __construct()
    {
        // 解析参数
        $this->paresArgs(func_get_args(), '');
    }
}
