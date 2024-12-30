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
use LinFly\Annotation\Bootstrap\AnnotationBootstrap;
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
            // 绝对路径开始的不拼接base路径
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
        if (str_contains($path, '*')) { // 通配符查找
            $iterator = glob($path);
        } else { // 按实际路径查找
            $iterator = new FilesystemIterator($path);
        }

        /** @var SplFileInfo|string $item */
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                $item = new SplFileInfo($item);
            }
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
     * 校验一个路径是否在允许的路径内
     * @param string $pathname
     * @return bool
     */
    public static function isInAllowedPath(string $pathname): bool
    {
        if (empty(AnnotationBootstrap::$config['include_paths'])) {
            return true;
        }
        return (bool)preg_match(AnnotationBootstrap::$config['include_regex_paths'], $pathname);
    }

    /**
     * 获取文件中的所有类
     * @param string $file
     * @return array
     */
    public static function getAllClassesInFile(string $file)
    {
        $classes = [];
        $tokens = token_get_all(file_get_contents($file));
        $count = count($tokens);
        // 兼容php7和php8
        $tNamespace = version_compare(PHP_VERSION, '8.0.0', '>=') ? [T_NAME_QUALIFIED, T_STRING] : [T_STRING];

        $namespace = '';
        for ($i = 2; $i < $count; $i++) {
            // 扫描到命名空间
            if ($tokens[$i - 2][0] === T_NAMESPACE && $tokens[$i - 1][0] === T_WHITESPACE) {
                // 清空命名空间
                $namespace = '';
                // 不是命名空间跳过
                if (!in_array($tokens[$i][0], $tNamespace)) {
                    continue;
                }
                // 获取命名空间
                $tempNamespace = $tokens[$i][1] ?? '';
                for ($j = $i + 1; $j < $count; $j++) {
                    // 如果是分号或者大括号，说明命名空间结束
                    if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                    if ($tokens[$j][0] === $tNamespace) {
                        // 命名空间拼接
                        $tempNamespace .= '\\' . $tokens[$j][1];
                    }
                }
                $namespace = $tempNamespace;
                // 扫描到类
            } else if (($tokens[$i - 2][0] === T_CLASS || $tokens[$i - 2][0] === T_ENUM) && $tokens[$i - 1][0] === T_WHITESPACE && $tokens[$i][0] === T_STRING) {
                // 拼接命名空间和类名
                $classes[] = ($namespace ? $namespace . '\\' : '') . $tokens[$i][1];
            }
        }

        return $classes;
    }
}
