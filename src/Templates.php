<?php

declare(strict_types=1);

namespace carmelosantana\RoKMonster;

use DirectoryIterator;

class Templates
{
    const AUTHOR = 'author';

    const TITLE = 'title';

    private string $dir;

    private array $templates = [];

    public function __construct($dir = null)
    {
        $this->dir = $dir;

        if (is_dir($this->dir))
            $this->loadTemplates();
    }

    public function get($template = null, $alt = false)
    {
        if ($template)
            return $this->templates[$template] ?? $alt;

        return $this->templates;
    }

    private function loadTemplates()
    {
        foreach (new DirectoryIterator($this->dir) as $di) {
            if ($di->isDot() or $di->isLink() or $di->getExtension() != 'json') continue;

            $this->templates[$di->getBasename('.json')] = json_decode(file_get_contents($di->getPathname()), true);
        }
    }
}
