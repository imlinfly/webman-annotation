<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/15 15:24:01
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Contracts;

interface IAnnotationParser
{
    /**
     * 注解处理
     * @access public
     * @param array $item
     * @return void
     */
    public static function process(array $item): void;
}
