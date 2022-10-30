<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/11 11:05:35
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation;

use Closure;
use Throwable;
use Generator;
use SplFileInfo;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionAttribute;
use ReflectionException;
use ReflectionParameter;
use LinFly\Annotation\Util\AnnotationUtil;
use Doctrine\Common\Annotations\AnnotationReader;
use LinFly\Annotation\Interfaces\IAnnotationItem;
use LinFly\Annotation\Interfaces\IAnnotationHandle;

abstract class Annotation
{
    /**
     * 注解处理类
     * @var array
     */
    protected static array $handle = [];

    /**
     * 注解类结果集
     * @var array
     */
    protected static array $annotations = [];

    /**
     * 注释解析器
     * @var AnnotationReader|null
     */
    protected static ?AnnotationReader $annotationReader = null;

    /**
     * 扫描注解类
     * @access public
     * @param array $include 扫描的路径
     * @param array $exclude 排除的路径
     * @return Generator
     */
    public static function scan(array $include, array $exclude = [])
    {
        // 排除路径转正则表达式
        $regular = AnnotationUtil::excludeToRegular($exclude);
        $excludeRegular = $regular ? '/^(' . $regular . ')/' : '';

        foreach ($include as $path) {
            // 扫描绝对路径
            $path = AnnotationUtil::basePath(AnnotationUtil::replaceSeparator($path));
            // 遍历获取文件
            yield from AnnotationUtil::findDirectory($path, function (SplFileInfo $item) use ($excludeRegular) {
                return $item->getExtension() === 'php' && !($excludeRegular && preg_match($excludeRegular, $item->getPathname()));
            });
        }
    }

    /**
     * 解析注解
     * @access public
     * @param Generator $generator
     * @return void
     * @throws ReflectionException
     */
    public static function parseAnnotations(Generator $generator): void
    {
        /** @var SplFileInfo $item */
        foreach ($generator as $item) {
            // 获取路径中的类名地址
            $pathname = $item->getPathname();
            $className = substr($pathname, strlen(AnnotationUtil::basePath()) + 1, -4);
            $className = str_replace('/', '\\', $className);

            try {
                if (!class_exists($className)) {
                    continue;
                }
                // 反射类
                $reflection = new ReflectionClass($className);
            } catch (Throwable) {
                continue;
            }

            // // 解析类的注解
            // $classAnnotation = self::parseClassAnnotations($reflection);
            // $classAnnotation['class'] = [$className => $classAnnotation['class']];
            //
            // // 循环解析类型 $type: class、method、property
            // foreach ($classAnnotation as $type => $items) {
            //     // 循环类型的解析类型
            //     // $name：className、methodName、propertyName、methodParameterName
            //     // $annotations: 对应类型的注解列表
            //     foreach ($items as $annotations) {
            //         if ($type === 'method') {
            //             $annotations = [...array_values($annotations['methods']), ...array_values($annotations['parameters'])];
            //         }
            //         // 循环解析的循环注解列表
            //         foreach ($annotations as $annotation) {
            //             // 循环注解的多个结果
            //             foreach ($annotation as $item) {
            //                 // 注解类
            //                 $annotationClass = $item['annotation'];
            //                 // 调用注解处理类
            //                 if (isset(self::$handle[$annotationClass])) {
            //                     /** @var IAnnotationHandle $handle */
            //                     foreach (self::$handle[$annotationClass] as $handle) {
            //                         [$handle, 'handle']($item, $className);
            //                     }
            //                 }
            //             }
            //         }
            //     }
            // }

            // 解析类的注解
            foreach (self::yieldParseClassAnnotations($reflection) as $annotations) {
                // 遍历注解结果集
                foreach ($annotations as $item) {
                    // 注解类
                    $annotationClass = $item['annotation'];
                    // 调用注解处理类
                    if (isset(self::$handle[$annotationClass])) {
                        /** @var IAnnotationHandle $handle */
                        foreach (self::$handle[$annotationClass] as $handle) {
                            [$handle, 'handle']($item, $className);
                        }
                    }
                }
            }
        }
    }

    /**
     * 解析类注解 包括：类注解、属性注解、方法注解、方法参数注解
     * @access public
     * @param string|ReflectionClass $className
     * @return array
     * @throws ReflectionException
     */
    public static function parseClassAnnotations(string|ReflectionClass $className): array
    {
        $reflectionClass = is_string($className) ? new ReflectionClass($className) : $className;

        $methods = $properties = [];

        // 获取类的注解
        $class = self::getClassAnnotations($reflectionClass);
        // 获取所有方法的注解
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            // 获取方法注解
            $method = self::getMethodAnnotations($reflectionMethod);
            // 获取方法参数注解
            $parameters = [];
            // 获取方法参数的注解
            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $parameter = self::getMethodParameterAnnotations($reflectionMethod, $reflectionParameter);
                $parameter && ($parameters[$reflectionParameter->name] = $parameter);
            }
            // 跳过空数据
            if (empty($method) && empty($parameters)) {
                continue;
            }

            $methods[$reflectionMethod->name] = [
                // 方法注解
                'methods' => $method,
                // 方法参数注解
                'parameters' => $parameters,
            ];
        }
        // 获取所有属性的注解
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $property = self::getPropertyAnnotations($reflectionClass, $reflectionProperty);
            $property && ($properties[$reflectionProperty->name] = $property);
        }

        return ['class' => $class, 'method' => $methods, 'property' => $properties];
    }

    /**
     * 解析类注解 包括：类注解、属性注解、方法注解、方法参数注解，利用Generator提高性能
     * @access public
     * @param string|ReflectionClass $className
     * @return Generator
     * @throws ReflectionException
     */
    public static function yieldParseClassAnnotations(string|ReflectionClass $className): Generator
    {
        $reflectionClass = is_string($className) ? new ReflectionClass($className) : $className;

        // 获取类的注解
        yield from self::getClassAnnotations($reflectionClass);
        // 获取所有方法的注解
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            // 获取方法注解
            $method = self::getMethodAnnotations($reflectionMethod);
            $method && (yield from $method);
            // 获取方法参数的注解
            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $parameter = self::getMethodParameterAnnotations($reflectionMethod, $reflectionParameter);
                $parameter && (yield from $parameter);
            }
        }
        // 获取所有属性的注解
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $property = self::getPropertyAnnotations($reflectionClass, $reflectionProperty);
            $property && (yield from $property);
        }
    }

    /**
     * 获取类注解
     * @access public
     * @param string|ReflectionClass $className
     * @param array|string $scanAnnotations
     * @return array
     * @throws ReflectionException
     */
    public static function getClassAnnotations(string|ReflectionClass $className, array|string $scanAnnotations = []): array
    {
        $scanAnnotations = (array)$scanAnnotations;

        $reflection = is_string($className) ? new ReflectionClass($className) : $className;

        $annotations = self::cache($reflection->getName(), 'class', function () use ($reflection) {
            // 扫描PHP8原生注解
            $attributes = $reflection->getAttributes();
            // 通过注释解析为注解
            $readerAttributes = self::getAnnotationReader()->getClassAnnotations($reflection);

            return self::buildScanAnnotationItems([...$attributes, ...$readerAttributes], [
                'type' => 'class',
                // 类名
                'class' => $reflection->name,
            ]);
        });

        return self::filterScanAnnotations($annotations, $scanAnnotations);
    }

    /**
     * 获取类方法注解
     * @access public
     * @param string|ReflectionMethod $methodName
     * @param array|string $scanAnnotations
     * @return array
     * @throws ReflectionException
     */
    public static function getMethodAnnotations(string|ReflectionMethod $methodName, array|string $scanAnnotations = []): array
    {
        $scanAnnotations = (array)$scanAnnotations;

        $reflectionMethod = is_string($methodName) ? new ReflectionMethod($methodName) : $methodName;
        // 类.方法名 标签
        $tag = 'method.' . $reflectionMethod->name;

        $annotations = self::cache($reflectionMethod->class, $tag, function () use ($reflectionMethod) {
            // 扫描PHP8原生注解
            $attributes = $reflectionMethod->getAttributes();
            // 通过注释解析为注解
            $readerAttributes = self::getAnnotationReader()->getMethodAnnotations($reflectionMethod);

            return self::buildScanAnnotationItems([...$attributes, ...$readerAttributes], [
                'type' => 'method',
                // 类名
                'class' => $reflectionMethod->class,
                // 方法名
                'method' => $reflectionMethod->name,
            ]);
        });

        return self::filterScanAnnotations($annotations, $scanAnnotations);
    }

    /**
     * 获取类方法注解
     * @access public
     * @param string|ReflectionClass $className
     * @param string|ReflectionProperty $propertyName
     * @param array|string $scanAnnotations
     * @return array
     * @throws ReflectionException
     */
    public static function getPropertyAnnotations(string|ReflectionClass $className, string|ReflectionProperty $propertyName, array|string $scanAnnotations = []): array
    {
        $scanAnnotations = (array)$scanAnnotations;

        $reflectionClass = is_string($className) ? new ReflectionClass($className) : $className;
        $reflectionProperty = is_string($propertyName) ? new ReflectionProperty($reflectionClass, $propertyName) : $propertyName;
        // 类.属性名 标签
        $tag = 'property.' . $reflectionProperty->name;

        $annotations = self::cache($reflectionClass->name, $tag, function () use ($reflectionProperty) {
            // 扫描PHP8原生注解
            $attributes = $reflectionProperty->getAttributes();
            // 通过注释解析为注解
            $readerAttributes = self::getAnnotationReader()->getPropertyAnnotations($reflectionProperty);

            return self::buildScanAnnotationItems([...$attributes, ...$readerAttributes], [
                'type' => 'property',
                // 类名
                'class' => $reflectionProperty->class,
                // 属性名
                'property' => $reflectionProperty->name,
            ]);
        });

        return self::filterScanAnnotations($annotations, $scanAnnotations);
    }

    /**
     * 获取方法参数注解
     * @access public
     * @param string|ReflectionMethod $methodName
     * @param string|ReflectionParameter $parameterName
     * @param array|string $scanAnnotations
     * @return array
     * @throws ReflectionException
     */
    public static function getMethodParameterAnnotations(string|ReflectionMethod $methodName, string|ReflectionParameter $parameterName, array|string $scanAnnotations = [])
    {
        $scanAnnotations = (array)$scanAnnotations;
        $reflectionMethod = is_string($methodName) ? new ReflectionMethod($methodName) : $methodName;

        // 解析反射的参数
        $reflectionParameter = is_string($parameterName) ? new ReflectionParameter([
            // 类名
            $reflectionMethod->class,
            // 方法名
            $reflectionMethod->name,

        ], $parameterName) : $parameterName;

        $tag = 'parameter.' . $reflectionMethod->name . '.' . $reflectionParameter->name;

        $annotations = self::cache($reflectionMethod->class, $tag, function () use ($reflectionMethod, $reflectionParameter) {
            // 扫描PHP8原生注解
            $attributes = $reflectionParameter->getAttributes();

            return self::buildScanAnnotationItems($attributes, [
                'type' => 'parameter',
                // 类名
                'class' => $reflectionMethod->class,
                // 方法名
                'method' => $reflectionMethod->name,
                // 参数名
                'parameter_name' => $reflectionParameter->name,
            ]);
        });

        return self::filterScanAnnotations($annotations, $scanAnnotations);
    }

    /**
     * Build ScanAnnotationItems
     * @access public
     * @param array $attributes
     * @param array $parameters
     * @return array
     */
    protected static function buildScanAnnotationItems(array $attributes, array $parameters = [])
    {
        $annotations = [];

        foreach ($attributes as $attribute) {

            if ($attribute instanceof ReflectionAttribute) {
                // 获取注解类实例
                /** @var IAnnotationItem $annotation */
                $annotation = self::reflectionAttributeToAnnotation($attribute);
            } else {
                $annotation = $attribute;
            }

            if (!$annotation instanceof IAnnotationItem) {
                continue;
            }

            $annotations[$annotation::class][] = array_merge([
                // 注解参数类
                'annotation' => $annotation::class,
                // 注解传入的参数
                'arguments' => $annotation->getArguments(),
                // 注解所有的参数
                'parameters' => $annotation->getParameters(),
            ], $parameters);

            unset($annotation);
        }

        return $annotations;
    }

    /**
     * 注解解析缓存
     * @access public
     * @param string $className
     * @param string $tag
     * @param array|Closure|null $data
     * @return array|Closure|false|mixed
     */
    public static function cache(string $className, string $tag, array|Closure $data = null)
    {
        if (is_null($data)) {
            return self::$annotations[$className][$tag] ?? false;
        }

        if ($data instanceof Closure) {
            return self::$annotations[$className][$tag] ??= $data();
        }

        self::$annotations[$className][$tag] ??= [];
        return self::$annotations[$className][$tag] = $data;
    }

    /**
     * 获取指定的ScanAnnotations
     * @access public
     * @param array $annotations
     * @param array $scanAnnotations
     * @return array
     */
    protected static function filterScanAnnotations(array $annotations, array $scanAnnotations): array
    {
        return $scanAnnotations ? array_filter($annotations, fn($key, $class) => in_array($class, $scanAnnotations)) : $annotations;
    }

    /**
     * 通过反射注解类获取注解类实例
     * @access public
     * @param ReflectionAttribute $attribute
     * @return mixed
     */
    protected static function reflectionAttributeToAnnotation(ReflectionAttribute $attribute)
    {
        $instance = $attribute->newInstance();
        return $instance->setArguments($attribute->getArguments());
    }

    /**
     * 获取注解处理类
     * @param string|null $annotation
     * @return array|string|null
     */
    public static function getHandle(string $annotation = null): array|string|null
    {
        return $annotation ? self::$handle[$annotation] ?? null : self::$handle;
    }

    /**
     * 添加注解处理类
     * @param string $annotationClass
     * @param string $handleClass
     * @return array
     */
    public static function addHandle(string $annotationClass, string $handleClass): array
    {
        self::$handle[$annotationClass] ??= [];
        self::$handle[$annotationClass][] = $handleClass;
        return self::$handle;
    }

    /**
     * 移除注解处理类
     * @param string $annotationClass
     * @param string|null $handleClass
     * @return array
     */
    public static function removeHandle(string $annotationClass, string $handleClass = null): array
    {
        if ($handleClass) {
            $key = array_search($handleClass, self::$handle[$annotationClass] ?? []);
            if ($key !== false) {
                unset(self::$handle[$annotationClass][$key]);
            }
        } else {
            unset(self::$handle[$annotationClass]);
        }

        return self::$handle;
    }

    /**
     * 获取注释解析器
     * @access public
     * @return AnnotationReader|null
     */
    public static function getAnnotationReader()
    {
        if (is_null(self::$annotationReader)) {
            self::$annotationReader = new AnnotationReader();
        }
        return clone self::$annotationReader;
    }
}
