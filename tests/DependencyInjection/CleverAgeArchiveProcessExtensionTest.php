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

namespace CleverAge\ArchiveProcessBundle\Tests\DependencyInjection;

use CleverAge\ArchiveProcessBundle\DependencyInjection\CleverAgeArchiveProcessExtension;
use CleverAge\ArchiveProcessBundle\Task\UnzipTask;
use CleverAge\ArchiveProcessBundle\Task\ZipTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(CleverAgeArchiveProcessExtension::class)]
class CleverAgeArchiveProcessExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'zip' => ['cleverage_archive_process.task.zip', ZipTask::class];
        yield 'unzip' => ['cleverage_archive_process.task.unzip', UnzipTask::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTasks')]
    public function testTaskIsRegistered(string $id, string $class): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeArchiveProcessExtension())->load([], $container);

        $definition = $container->getDefinition($id);
        self::assertSame($class, $definition->getClass());
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());

        // Referenced as '@<class>' in process configurations
        $alias = $container->getAlias($class);
        self::assertSame($id, (string) $alias);
        self::assertTrue($alias->isPublic());
    }
}
