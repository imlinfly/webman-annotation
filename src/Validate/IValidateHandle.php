<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/14 14:37:22
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Validate;

use Webman\Http\Request;

interface IValidateHandle
{
    /**
     * 验证器验证处理
     * @access public
     * @param Request $request
     * @param array $parameters
     * @return bool|string
     */
    public static function handle(Request $request, array $parameters): bool|string;
}
