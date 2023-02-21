<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/10 10:08:45
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Route;

use Attribute;
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS)]
class NamespaceController extends AbstractAnnotation
{
    /**
     * @param string|array $path 自定义控制器路径 变量{$className}值为删除命名空间前缀后转小驼峰的名称
     * @param string $namespace 需要删除的命名空间前缀
     * @param null|callable $filter 自定义过滤器
     */
    public function __construct(string|array $path = '/{$className}', public string $namespace = '', ?callable $filter = null)
    {
        // 解析参数
        $this->paresArgs(func_get_args(), 'path');
    }

    public static function camel(string $value): string
    {
        $values = explode('/', $value);

        $result = '';

        foreach ($values as $v) {
            $result .= '/' . lcfirst($v);
        }

        return $result;
    }
}
