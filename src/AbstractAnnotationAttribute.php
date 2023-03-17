<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:48:37
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation;

use LinFly\Annotation\Contracts\IAnnotationAttribute;

abstract class AbstractAnnotationAttribute implements IAnnotationAttribute
{
    /**
     * 注解传入的参数
     * @var array
     */
    protected array $_arguments = [];

    /**
     * 参数名
     * @var array
     */
    protected array $_parameters = [];

    /**
     * 额外的参数值 [key => value]
     * @var array
     */
    protected array $_extraValues = [];

    /**
     * 参数默认值
     * @var array
     */
    protected array $_defaultValues = [];

    /**
     * 设置参数值
     * @access public
     * @param array $arguments
     * @return array
     */
    protected function setArguments(array $arguments): array
    {
        // 解析参数名
        $this->paresParameters();
        // 设置参数值
        return $this->_arguments = $arguments;
    }

    /**
     * 解析参数
     * @access public
     * @return void
     */
    protected function paresParameters(): void
    {
        // 使用反射获取构造方法参数
        $parameters = (new \ReflectionObject($this))->getConstructor()->getParameters();
        // 获取参数名
        $this->_parameters = array_map(function ($param) {
            // 参数默认值
            if ($param->isDefaultValueAvailable()) {
                $this->_defaultValues[$param->getName()] = $param->getDefaultValue();
            }
            return $param->getName();
        }, $parameters);
    }

    /**
     * 获取传入的参数
     * @return array
     */
    public function getArguments(): array
    {
        return $this->_arguments;
    }

    /**
     * 获取所有的参数
     * @access public
     * @return array
     */
    public function getParameters(): array
    {
        $params = $this->_extraValues;

        foreach ($this->_parameters as $value) {
            $params[$value] = $this->_arguments[$value] ?? $this->_defaultValues[$value] ?? null;
        }

        return $params;
    }

    /**
     * 获取注解处理类
     * @return string|array
     */
    public static function getParser(): string|array
    {
        return [];
    }
}
