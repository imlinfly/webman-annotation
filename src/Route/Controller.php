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
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Controller extends AbstractAnnotation
{
    /**
     * @param string|array $prefix 路由分组路径
     */
    public function __construct(public string|array $prefix = '')
    {
        // 解析参数
        $this->paresArgs(func_get_args(), 'prefix');
    }
}
