<?php

namespace Felix\Sey;

class Sey
{
    protected static array $functions = [
        'sqrt'   => 'bcsqrt',
        'powmod' => 'bcpowmod',
        'pi'     => 'bcpi',
        '!'      => 'bcfact',
    ];

    protected static int $precision = 16;

    public static function define(string $function, callable $definition): void
    {
        static::$functions[$function] = $definition;
    }

    public static function precision(?int $precision = null): int
    {
        if ($precision === null) {
            return static::$precision;
        }

        static::$precision = $precision;

        return $precision;
    }

    public static function parse(string $expression, array $variables = [], array $functions = [], ?int $precision = null): string
    {
        $expression = trim($expression);

        if ($expression === '') {
            return '0';
        }

        $runtime = new Runtime(
            $expression,
            $variables,
            array_merge(static::$functions, $functions),
            $precision ?? static::$precision
        );

        return $runtime->run();
    }
}
