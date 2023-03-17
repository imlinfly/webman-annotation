<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/15 15:31:00
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Validate;

use LinFly\Annotation\Bootstrap\AnnotationBootstrap;
use LinFly\Annotation\Parser\ValidateAnnotationParser;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ValidateMiddleware implements MiddlewareInterface
{
    /**
     * process
     * @access public
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $handler): Response
    {
        /** @var IValidateHandle $validateHandle */
        // 无验证器验证 或 验证器验证处理类为空则不处理
        if (!ValidateAnnotationParser::isExistValidate($request->controller, $request->action)
            || empty($validateHandle = AnnotationBootstrap::$config['validate']['handle'] ?? '')) {
            return $handler($request);
        }

        // 获取验证器列表
        $generator = ValidateAnnotationParser::getValidates($request->controller, $request->action);

        foreach ($generator as $item) {

            $params = [
                'data' => $this->getData($request, (array)$item['params']),
                'validate' => $item['validate'],
                'scene' => $item['scene'],
            ];

            // 验证器验证处理
            if (true !== ($result = $validateHandle::handle($request, $params))) {
                // 调用验证器验证失败处理
                return (AnnotationBootstrap::$config['validate']['fail_handle'] ?? function (Request $request, string $message) {
                    return json(['code' => 500, 'msg' => $message]);
                })($request, (string)$result);
            }
        }

        return $handler($request);
    }

    public function getData(Request $request, array $params)
    {
        $data = [];

        foreach ($params as $param) switch ($param) {
            case '$post':
                $data = array_merge($data, $request->get());
                break;
            case '$get':
                $data = array_merge($data, $request->post());
                break;
            case '$all':
                $data = array_merge($data, $request->all());
                break;
            default:
                if (str_starts_with($param, '$get.')) {
                    $name = substr($param, 5);
                    $data[$name] = $request->get($name);
                } elseif (str_starts_with($param, '$post.')) {
                    $name = substr($param, 6);
                    $data[$name] = $request->post($name);
                } else {
                    $data[$param] = $request->input($param);
                }
                break;
        }

        return $data;
    }
}
