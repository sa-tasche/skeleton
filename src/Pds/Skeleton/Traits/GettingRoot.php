<?php

declare(strict_types=1);

namespace Pds\Skeleton\Traits;

trait GettingRoot
{
    protected function gettingRoot() : string
    {
        $root = $root ?? \getcwd();
        $root = \dirname($root, 4);

        return $root;
    }
}