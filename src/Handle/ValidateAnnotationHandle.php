<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/15 15:07:57
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Handle;

use Generator;
use LinFly\Annotation\Interfaces\IAnnotationHandle;

class ValidateAnnotationHandle implements IAnnotationHandle
{
    /**
     * 验证器注解列表
     * @var array
     */
    protected static array $validates = [];

    /**
     * 注解处理
     * @access public
     * @param array $item
     * @return void
     */
    public static function handle(array $item): void
    {
        if ($item['type'] == 'class') {
            $key = $item['class'];
        } elseif ($item['type'] == 'method') {
            $key = $item['class'] . '::' . $item['method'];
        } else {
            throw new \InvalidArgumentException('验证器注解不支持在' . $item['type'] . '使用');
        }

        self::$validates[$key] ??= [];

        if (empty($item['parameters']['validate'])) {
            return;
        } else if (is_array($item['parameters']['validate'])) {
            // 添加多个验证器
            foreach ($item['parameters']['validate'] as $validate) {
                $data = $item['parameters'];
                $data['validate'] = $validate;
                self::$validates[$key][] = $data;
            }
        } else {
            self::$validates[$key][] = $item['parameters'];
        }
    }

    /**
     * 是否存在验证器
     * @access public
     * @param string $class
     * @param string $method
     * @return bool
     */
    public static function isExistValidate(string $class, string $method): bool
    {
        return isset(self::$validates[$class]) || isset(self::$validates[$class . '::' . $method]);
    }

    /**
     * 获取验证器列表
     * @access public
     * @param string $class
     * @param string|null $method
     * @return Generator
     */
    public static function getValidates(string $class, string $method = null): Generator
    {
        // 类验证器
        yield from self::$validates[$class] ?? [];
        // 类方法验证器
        $method && yield from self::$validates[$class . '::' . $method] ?? [];
    }
}
