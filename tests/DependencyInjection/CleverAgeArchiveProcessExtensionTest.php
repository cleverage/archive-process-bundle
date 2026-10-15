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
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'zip' => [ZipTask::class, 'archive.zip', 'cleverage_archive_process.task.zip'];
        yield 'unzip' => [UnzipTask::class, 'archive.unzip', 'cleverage_archive_process.task.unzip'];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTasks')]
    public function testTaskIsRegistered(string $class, string $code, string $deprecatedId): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeArchiveProcessExtension())->load([], $container);

        // The class name is the service id
        $definition = $container->getDefinition($class);
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());
        // Referenced by its code in the process configurations (task: <code>)
        self::assertSame([['code' => $code]], $definition->getTag('cleverage.task'));
        // Public for the direct access from the service container, deprecated
        self::assertTrue($definition->isPublic());
        self::assertSame([['package' => 'cleverage/archive-process-bundle', 'version' => '2.3']], $definition->getTag('container.private'));

        // Former id, deprecated
        $alias = $container->getAlias($deprecatedId);
        self::assertSame($class, (string) $alias);
        self::assertFalse($alias->isPublic());
        self::assertTrue($alias->isDeprecated());
        self::assertSame(
            ['package' => 'cleverage/archive-process-bundle', 'version' => '2.3', 'message' => 'The "'.$deprecatedId.'" service alias is deprecated, use the "'.$code.'" task code or the class name of the task instead. It will be removed in 3.0.'],
            $alias->getDeprecation($deprecatedId)
        );
    }
}
