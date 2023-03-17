<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/15 15:24:01
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Contracts;

interface IAnnotationAttribute
{
    /**
     * 获取传入的参数
     * @return array
     */
    public function getArguments(): array;

    /**
     * 获取所有的参数
     * @access public
     * @return array
     */
    public function getParameters(): array;

    /**
     * 获取注解处理类
     * @return string|array
     */
    public static function getParser(): string|array;
}
