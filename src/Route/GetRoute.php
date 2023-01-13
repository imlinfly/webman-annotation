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
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class GetRoute extends AbstractAnnotation
{
    public array $_extraValues = [
        'methods' => ['GET'],
    ];

    public function __construct(public string|array $path = '', public string $name = '', public array $params = [])
    {
        $this->paresArgs(func_get_args(), 'path');
    }
}
