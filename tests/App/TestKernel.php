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

namespace CleverAge\ArchiveProcessBundle\Tests\App;

use CleverAge\ArchiveProcessBundle\CleverAgeArchiveProcessBundle;
use CleverAge\ProcessBundle\CleverAgeProcessBundle;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Application used by the integration tests: process-bundle and this bundle, in a temporary directory of its own (per
 * PHP process).
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new CleverAgeProcessBundle();
        yield new CleverAgeArchiveProcessBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getRunDir().'/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getRunDir().'/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'validation' => ['email_validation_mode' => 'html5'],
        ]);
        if (Kernel::VERSION_ID >= 70300) {
            // Not setting it is deprecated since Symfony 7.3: its default value changes in 8.0
            $container->extension('framework', ['property_info' => ['with_constructor_extractor' => true]]);
        }

        // Tasks referenced by code (task: <code>): process-bundle 5.4 or later
        if (class_exists(TaskRegistry::class)) {
            $container->extension('clever_age_process', [
                'configurations' => [
                    'test.archive' => [
                        'tasks' => [
                            'zip' => [
                                'task' => 'archive_zip',
                                'options' => ['filename' => 'archive.zip', 'files' => [], 'files_base_path' => '/tmp'],
                            ],
                            'unzip' => [
                                'task' => 'archive_unzip',
                                'options' => ['filename' => 'archive.zip', 'destination' => '/tmp'],
                            ],
                        ],
                    ],
                ],
            ]);
            $services = $container->services();
            $services->alias('test.task_registry', TaskRegistry::class)->public();
            $services->alias('test.process_configuration_registry', ProcessConfigurationRegistry::class)->public();
        }
    }

    private function getRunDir(): string
    {
        return sys_get_temp_dir().'/archive_process_bundle_tests_'.getmypid();
    }
}
