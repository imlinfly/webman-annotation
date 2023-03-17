<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/16 16:53:02
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Parser;

use LinFly\Annotation\Attributes\Bean;
use LinFly\Annotation\Contracts\IAnnotationParser;
use LinFly\FacadeContainer;

class ContainerAnnotationParser implements IAnnotationParser
{
    public static function process(array $item): void
    {
        if ($item['annotation'] == Bean::class) {
            self::definition($item);
        }
    }

    public static function definition(array $item): void
    {
        $params = $item['parameters'];

        if ($params['name']) {
            FacadeContainer::definition($params['name'], $item['class']);
        }
    }
}
