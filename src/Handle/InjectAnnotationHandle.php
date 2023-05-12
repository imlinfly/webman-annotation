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
use LinFly\Annotation\Annotation\Inject;
use LinFly\Annotation\Interfaces\IAnnotationHandle;
use LinFly\Exception\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use support\Container;

class InjectAnnotationHandle implements IAnnotationHandle
{
    private static array $inject = [];

    public static function handle(array $item): void
    {
        if ($item['annotation'] == Inject::class) {
            self::parseInject($item);
        }
    }

    public static function parseInject(array $item)
    {
        $class = $item['class'];
        $parameters = $item['parameters'];
        $property = $item['property'];

        try {
            $reflectionProperty = new ReflectionProperty($class, $property);
        } catch (ReflectionException $e) {
            throw new NotFoundException($e->getMessage());
        }

        if ($parameters['name']) {
            $name = $parameters['name'];
        } else if ($reflectionProperty->hasType() && !$reflectionProperty->getType()->isBuiltin()) {
            $name = $reflectionProperty->getType()->getName();
        } else {
            // 获取属性的注释
            $document = $reflectionProperty->getDocComment();

            // 获取注释中的类型
            if (!preg_match('/\*\s+@var\s+(\S+)/', $document, $matches) || !isset($matches[1])) {
                throw new NotFoundException('Inject annotation must have a type');
            }

            // 注入的类名
            $name = $matches[1];
            $lowerName = strtolower($name);

            $reflectionClass = $reflectionProperty->getDeclaringClass();

            // 获取类使用到的类列表
            $phpParser = new PhpParser();
            $stmt = $phpParser->parseUseStatements($reflectionClass);

            if (isset($stmt[$lowerName])) {
                $name = $stmt[$lowerName];
            } else {
                // 获取类的命名空间
                $namespace = $reflectionClass->getNamespaceName();
                $name = ($namespace ? $namespace . '\\' : '') . $name;
            }
        }

        // 注入的参数
        self::$inject[$class][$property] = [
            'name' => $name,
            'parameters' => $parameters['parameters'],
        ];
    }

    public static function bindCallbackBeforeCall(object $instance, string $name, array $arguments, ReflectionClass $reflectorClass)
    {
        // 获取实例的属性列表
        foreach ($reflectorClass->getProperties() as $reflectorProperty) {
            $propertyName = $reflectorProperty->getName();
            $className = $reflectorProperty->class;
            if (isset(self::$inject[$className][$propertyName])) {
                // 获取注入属性的参数
                $item = self::$inject[$className][$propertyName];
                // 设置属性可访问
                $reflectorProperty->setAccessible(true);
                // 获取注入的实例
                $value = Container::instance()->getSingle($item['name'], $item['parameters']);
                // 设置属性值
                $reflectorProperty->setValue($instance, $value);
            }
        }
    }
}
