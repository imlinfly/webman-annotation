<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2022/10/21 21:08:44
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace LinFly\Annotation\Util;

use Closure;
use FilesystemIterator;
use Generator;
use SplFileInfo;

abstract class AnnotationUtil
{
    /**
     * 排除路径转正则表达式
     * @access public
     * @param array $exclude
     * @return string
     */
    public static function excludeToRegular(array $exclude): string
    {
        $regular = '';

        foreach ($exclude as $value) {
            // 绝对路径开始的不拼接root路径
            if (!str_starts_with($value, '/')) {
                $value = self::basePath($value);
            }
            $value = preg_quote($value);
            $value = str_replace(['/', '\*'], ['\/', '.*'], $value);
            $regular .= $value . ')|(';
        }

        return substr($regular, 0, -3);
    }

    /**
     * 通过目录查找文件
     * @access public
     * @param string $path
     * @param Closure $filter
     * @return Generator
     */
    public static function findDirectory(string $path, Closure $filter): Generator
    {
        $iterator = new FilesystemIterator($path);

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {

            if ($item->isDir() && !$item->isLink()) {
                yield from self::findDirectory($item->getPathname(), $filter);
            } else {
                if ($filter($item)) {
                    yield $item;
                }
            }
        }
    }

    /**
     * 替换路径分隔符
     * @access public
     * @param string $path
     * @return string
     */
    public static function replaceSeparator(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * 获取根目录路径
     * @access public
     * @param string $path
     * @return string
     */
    public static function basePath(string $path = ''): string
    {
        $path = base_path($path);
        return self::replaceSeparator($path);
    }

    /**
     * 数组转字符串
     * @access public
     * @param array|string $separator 分隔符
     * @param array|null $array 数组
     * @param bool $removeNullValue 是否移除空值
     * @return string
     */
    public static function implode(array|string $separator = '', ?array $array = null, bool $removeNullValue = true): string
    {
        if (is_array($separator)) {
            $array = $separator;
            $separator = '';
        }

        $result = '';

        foreach ($array as $value) {
            // 移除空值
            if ($removeNullValue && empty($value)) {
                continue;
            }

            $result .= $value . $separator;
        }

        return rtrim($result, $separator);
    }
}
