<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:08:45
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Attributes;

use Attribute;
use LinFly\Annotation\AbstractAnnotationAttribute;
use LinFly\Annotation\Parser\ContainerAnnotationParser;

/**
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Bean extends AbstractAnnotationAttribute
{
    /**
     * @param string $name 实例别名
     */
    public function __construct(protected string $name = '')
    {
        $this->setArguments(func_get_args());
    }

    public static function getParser(): string
    {
        return ContainerAnnotationParser::class;
    }
}
