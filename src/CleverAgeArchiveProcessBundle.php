<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ArchiveProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ArchiveProcessBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CleverAgeArchiveProcessBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
