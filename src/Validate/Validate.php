<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/14 14:35:44
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Validate;

use Attribute;
use Doctrine\Common\Annotations\Annotation\Target;
use LinFly\Annotation\AbstractAnnotationAttribute;
use LinFly\Annotation\Parser\ValidateAnnotationParser;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Validate extends AbstractAnnotationAttribute
{
    /**
     * 验证器注解类
     * @param string|array $params 验证器参数 支持多个，例如: ['$get.id', '$post.name', '$post.title', ...]
     * 验证器参数 支持：
     * $post 获取所有 POST 参数
     * $get 获取所有 GET 参数
     * $all 获取所有 REQUEST 参数
     * $post.xx 自定义 POST 参数名称 xx为实际的参数名称
     * $get.xx 自定义 GET 参数名称 xx为实际的参数名称
     * xx 自定义 REQUEST 参数名称 xx为实际的参数名称
     * @param string $validate 验证器类名
     * @param string $scene 验证场景
     */
    public function __construct(public string|array $params = '$all', public string $validate = '', public string $scene = '')
    {
        // 解析参数
        $this->setArguments(func_get_args(), 'params');
    }

    public static function getParser(): string
    {
        return ValidateAnnotationParser::class;
    }
}
