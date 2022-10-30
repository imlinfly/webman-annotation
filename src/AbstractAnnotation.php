<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:48:37
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation;

use LinFly\Annotation\Interfaces\IAnnotationItem;

abstract class AbstractAnnotation implements IAnnotationItem
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
     * 参数默认值
     * @var array
     */
    protected array $_defaultValues = [];

    /**
     * 解析参数
     * @access public
     * @param array $args
     * @param string $firstParameter
     * @return array
     */
    protected function paresArgs(array $args, string $firstParameter): array
    {
        // 解析参数
        $this->paresParameters();

        // 非注释解析传参
        if (isset($args[1])) {
            return $this->_arguments;
        }
        // 注释解析 不指定参数传参
        if (isset($args[0]['value'][1]) && is_array($args[0]['value']) && isset($this->_parameters[1])) {
            $data = [];
            foreach ($args[0]['value'] as $key => $value) {
                $data[$this->_parameters[$key]] = $value;
            }
            return $this->_arguments = $data;
        }

        // 注释解析 指定参数传参
        $args = $args[0];
        if (isset($args['value'])) {
            $args[$firstParameter] = $args['value'];
            unset($args['value']);
        }
        if (is_array($args)) {
            $this->_arguments = $args;
        }

        return $this->_arguments;
    }

    /**
     * 解析参数
     * @access public
     * @return void
     */
    protected function paresParameters()
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
     * 动态设置参数
     * @param array $args
     * @return static
     */
    public function setArguments(array $args): static
    {
        $this->_arguments = [];

        if (isset($args[0])) {
            foreach ($args as $index => $value) {
                if (is_string($index)) {
                    $index = array_search($index, $this->_parameters);
                }
                $this->_arguments[$this->_parameters[$index]] = $value;
            }
        } else {
            $this->_arguments = $args;
        }

        return $this;
    }

    /**
     * 获取所有的参数
     * @access public
     * @return array
     */
    public function getParameters(): array
    {
        $params = [];

        foreach ($this->_parameters as $value) {
            $params[$value] = $this->_arguments[$value] ?? $this->_defaultValues[$value] ?? null;
        }

        return $params;
    }
}
