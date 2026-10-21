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
use CleverAge\ArchiveProcessBundle\DependencyInjection\CleverAgeArchiveProcessExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CleverAgeArchiveProcessBundle::class)]
#[UsesClass(CleverAgeArchiveProcessExtension::class)]
class CleverAgeArchiveProcessBundleTest extends TestCase
{
    public function testPathIsTheBundleRoot(): void
    {
        $path = (new CleverAgeArchiveProcessBundle())->getPath();

        self::assertSame(\dirname(__DIR__), $path);
        self::assertDirectoryExists($path.'/config/services');
    }

    public function testExtensionAlias(): void
    {
        $bundle = new CleverAgeArchiveProcessBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(CleverAgeArchiveProcessExtension::class, $extension);
        self::assertSame('cleverage_archive_process', $extension->getAlias());
        // Built once
        self::assertSame($extension, $bundle->getContainerExtension());
    }
}
