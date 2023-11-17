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
use LinFly\Annotation\Bootstrap\AnnotationBootstrap;
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
            throw new \RuntimeException('注解验证器不支持在' . $item['type'] . '使用');
        }

        self::$validates[$key] ??= [];

        // 自动验证器
        if (empty($item['parameters']['validate'])) {
            $item['parameters']['validate'] = self::getAutoValidateClass($item);
        }

        if (is_array($item['parameters']['validate'])) {
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
     * 获取自动验证器类
     * @param array $item
     * @return string
     */
    protected static function getAutoValidateClass(array $item): string
    {
        // 通过命名空间拼接规则获取验证器类名
        $autoValidate = AnnotationBootstrap::$config['validate']['auto_validate'] ?? true;

        if (!$autoValidate) {
            $errorMessage = sprintf(
                '方法 %s->%s() 中的 @Validate() 注解要求 "validate" 参数不能为空，' .
                '或者在配置文件[/config/plugin/linfly/annotation/annotation.php]中将 "auto_validate" 设置为 true。',
                $item['class'], $item['method']
            );
            throw new \RuntimeException($errorMessage);
        }

        // 验证器类名后缀
        $suffix = AnnotationBootstrap::$config['validate']['auto_validate_suffix'];
        // 自动验证器处理
        $handle = AnnotationBootstrap::$config['validate']['auto_validate_handle'] ?? function (array $item) {
            return str_replace('\\controller\\', '\\validate\\', $item['class']);
        };

        $class = $handle($item) . $suffix;

        if (!class_exists($class)) {
            $errorMessage = sprintf(
                '方法 %s->%s() 的自动验证器 %s 不存在。',
                $item['class'], $item['method'], $class
            );
            throw new \RuntimeException($errorMessage);
        }

        return $class;
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
