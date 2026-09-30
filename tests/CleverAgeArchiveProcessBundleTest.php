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

namespace CleverAge\ArchiveProcessBundle\Tests;

use CleverAge\ArchiveProcessBundle\CleverAgeArchiveProcessBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CleverAgeArchiveProcessBundle::class)]
class CleverAgeArchiveProcessBundleTest extends TestCase
{
    public function testPathIsTheBundleRoot(): void
    {
        $path = (new CleverAgeArchiveProcessBundle())->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path.'/config/services');
    }
}
