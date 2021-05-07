<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use DirectoryIterator;

class Templates
{
    const AUTHOR = 'author';

    const TITLE = 'title';

    private array $templates = [];

    public function get($template = null, $alt = false)
    {
        if ($template)
            return $this->templates[$template] ?? $alt;

        return $this->templates;
    }

    public function load($dir)
    {
        // add trailing slash
        if (substr($dir, -1) == DIRECTORY_SEPARATOR)
            $dir .= DIRECTORY_SEPARATOR;

        if (!is_dir($dir))
            return $this;

        foreach (new DirectoryIterator($dir) as $di) {
            if ($di->isDot() or $di->isLink() or $di->getExtension() != 'json') continue;

            $this->templates[$di->getBasename('.json')] = json_decode(file_get_contents($di->getPathname()), true);
        }

        return $this;
    }
}
