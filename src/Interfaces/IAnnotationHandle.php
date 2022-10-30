<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/15 15:24:01
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Interfaces;

interface IAnnotationHandle
{
    /**
     * 注解处理
     * @access public
     * @param array $item
     * @param string $className
     * @return void
     */
    public static function handle(array $item, string $className): void;
}
