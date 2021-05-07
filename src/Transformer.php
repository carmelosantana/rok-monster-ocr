<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

class Transformer
{
    public static function strRemoveNonNumeric(string $string): string
    {
        return preg_replace('/[^0-9,.]+/', '', $string);
    }

    public static function strRemoveExtraLineBreaks(string $string): string
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
    }
}
