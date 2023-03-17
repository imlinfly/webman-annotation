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
class PatchMapping extends AbstractAnnotationAttribute
{
    public array $_extraValues = [
        'methods' => ['PATCH'],
    ];

    public function __construct(public string $path = '', public string $name = '', public array $params = [])
    {
        $this->setArguments(func_get_args());
    }

    public static function getParser(): string
    {
        return RouteAnnotationParser::class;
    }
}
