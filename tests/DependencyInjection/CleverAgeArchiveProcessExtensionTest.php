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
use CleverAge\ArchiveProcessBundle\DependencyInjection\Configuration;
use CleverAge\ArchiveProcessBundle\Task\UnzipTask;
use CleverAge\ArchiveProcessBundle\Task\ZipTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(CleverAgeArchiveProcessExtension::class)]
#[CoversClass(Configuration::class)]
class CleverAgeArchiveProcessExtensionTest extends TestCase
{
    public function testEveryServiceDefinitionFileIsLoaded(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeArchiveProcessExtension())->load([], $container);

        $files = glob(\dirname(__DIR__, 2).'/config/services/*.yaml');
        self::assertIsArray($files);
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $config = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);
            self::assertIsArray($config);
            if (!isset($config['services'])) {
                continue;
            }
            self::assertIsArray($config['services']);
            foreach (array_keys($config['services']) as $id) {
                if ('_defaults' === $id) {
                    continue;
                }
                if (str_ends_with((string) $id, '\\')) {
                    // Resource: at least one service registered under the namespace prefix
                    $registered = array_filter(
                        array_keys($container->getDefinitions()),
                        static fn (string $definitionId): bool => str_starts_with($definitionId, (string) $id)
                    );
                    self::assertNotEmpty($registered, \sprintf('No service registered for the "%s" resource of %s', $id, basename($file)));
                    continue;
                }
                self::assertTrue($container->has((string) $id), \sprintf('Service "%s" of %s is not registered', $id, basename($file)));
            }
        }
    }

    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'zip' => [ZipTask::class, 'archive_zip', 'cleverage_archive_process.task.zip'];
        yield 'unzip' => [UnzipTask::class, 'archive_unzip', 'cleverage_archive_process.task.unzip'];
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

    public function testEveryTaskIsTested(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeArchiveProcessExtension())->load([], $container);

        $ids = array_filter(
            array_keys($container->getDefinitions()),
            static fn (string $id): bool => str_starts_with($id, 'CleverAge\\ArchiveProcessBundle\\Task\\')
        );
        self::assertEqualsCanonicalizing(array_column(iterator_to_array(self::provideTasks()), 0), array_values($ids));

        // Every task has its own code, prefixed by the bridge name
        $codes = array_column(iterator_to_array(self::provideTasks()), 1);
        self::assertSame($codes, array_unique($codes));
        foreach ($codes as $code) {
            self::assertStringStartsWith('archive_', $code);
        }

        // Every former id is a deprecated alias
        $aliases = array_filter(
            array_keys($container->getAliases()),
            static fn (string $id): bool => str_starts_with($id, 'cleverage_archive_process.task.')
        );
        self::assertEqualsCanonicalizing(array_column(iterator_to_array(self::provideTasks()), 2), array_values($aliases));
    }

    public function testConfigurationKeysAreDeprecated(): void
    {
        $deprecations = $this->collectDeprecations(static function (): void {
            (new CleverAgeArchiveProcessExtension())->load([[], ['foo' => true], ['bar' => 1, 'foo' => false]], new ContainerBuilder());
        });

        self::assertSame(['Since cleverage/archive-process-bundle 2.3: Configuring "clever_age_archive_process" is deprecated, the bundle has no configuration: the keys "foo", "bar" are ignored. They will be rejected in 3.0.'], $deprecations);
    }

    public function testNoDeprecationWithoutConfigurationKey(): void
    {
        $deprecations = $this->collectDeprecations(static function (): void {
            (new CleverAgeArchiveProcessExtension())->load([[], []], new ContainerBuilder());
        });

        self::assertSame([], $deprecations);
    }

    public function testConfigurationHasNoOption(): void
    {
        $configuration = (new CleverAgeArchiveProcessExtension())->getConfiguration([], new ContainerBuilder());

        self::assertInstanceOf(Configuration::class, $configuration);
        self::assertSame('clever_age_archive_process', $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }

    /**
     * @param callable(): void $callback
     *
     * @return list<string>
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
