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

namespace CleverAge\ArchiveProcessBundle\Tests\Functional;

use CleverAge\ArchiveProcessBundle\Task\UnzipTask;
use CleverAge\ArchiveProcessBundle\Task\ZipTask;
use CleverAge\ArchiveProcessBundle\Tests\App\TestKernel;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The bundle in an application with process-bundle (see TestKernel): the container compiles, the services of the
 * bundle are resolved with their dependencies.
 */
#[CoversNothing]
class KernelTest extends KernelTestCase
{
    private mixed $exceptionHandler;

    protected function setUp(): void
    {
        $this->exceptionHandler = $this->getExceptionHandler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // The ErrorHandler registered when the kernel boots is not removed by the first Symfony 6.4 versions
        if ($this->getExceptionHandler() !== $this->exceptionHandler) {
            restore_exception_handler();
        }
    }

    public function testTasksAreRegistered(): void
    {
        $container = self::getContainer();

        self::assertTrue($container->has(ZipTask::class));
        self::assertTrue($container->has(UnzipTask::class));
    }

    public function testTasksAreResolvedByCode(): void
    {
        if (!class_exists(TaskRegistry::class)) {
            self::markTestSkipped('The tasks are referenced by code since cleverage/process-bundle 5.4.');
        }
        $processConfigurationRegistry = self::getContainer()->get('test.process_configuration_registry');
        self::assertInstanceOf(ProcessConfigurationRegistry::class, $processConfigurationRegistry);
        $taskRegistry = self::getContainer()->get('test.task_registry');
        self::assertInstanceOf(TaskRegistry::class, $taskRegistry);

        $processConfiguration = $processConfigurationRegistry->getProcessConfiguration('test.archive');
        self::assertInstanceOf(ZipTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('zip')));
        self::assertInstanceOf(UnzipTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('unzip')));
    }

    private function getExceptionHandler(): mixed
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
