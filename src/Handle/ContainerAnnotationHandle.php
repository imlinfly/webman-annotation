<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/12/16 16:53:02
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Handle;

use Doctrine\Common\Annotations\PhpParser;
use LinFly\Annotation\Annotation\Bean;
use LinFly\Annotation\Annotation\Inject;
use LinFly\Annotation\Interfaces\IAnnotationHandle;
use LinFly\Exception\NotFoundException;
use LinFly\FacadeContainer;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use support\Container;

class ContainerAnnotationHandle implements IAnnotationHandle
{
    public static function handle(array $item): void
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
