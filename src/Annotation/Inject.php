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
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject extends AbstractAnnotation
{
    /**
     * @param string|array $name 实例或者别名
     * @param array $parameters 参数
     */
    public function __construct(protected string|array $name = '', protected array $parameters = [])
    {
        $this->paresArgs(func_get_args(), 'name');
    }
}
