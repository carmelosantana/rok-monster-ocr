<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use carmelosantana\RoKMonster\TinyCLI;

class Transformer
{
    public static function str_remove_non_numeric(string $string): string
    {
        return preg_replace('/[^0-9,.]+/', '', $string);
    }

    public static function str_remove_extra_lines(string $string): string
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
    }

    public static function text_remove_non_numeric(string $string): string
    {
        TinyCLI::cli_echo('This is deprecated: text_remove_non_numeric()');
        return self::str_remove_non_numeric($string);
    }
}
