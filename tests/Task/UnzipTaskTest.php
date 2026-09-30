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

namespace CleverAge\ArchiveProcessBundle\Tests\Task;

use CleverAge\ArchiveProcessBundle\Task\UnzipTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(UnzipTask::class)]
class UnzipTaskTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/'.uniqid('unzip_task_test_', true);
        mkdir($this->dir);
        $this->createArchive($this->dir.'/archive1.zip', 'file1.txt');
        $this->createArchive($this->dir.'/archive2.zip', 'file2.txt');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testUnzip(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
        ]);

        self::assertSame($this->dir.'/destination', $this->execute($task, $state, null));
        self::assertFileExists($this->dir.'/destination/file1.txt');
    }

    public function testEachInputIsUsed(): void
    {
        [$task, $state] = $this->createTask([]);

        $output1 = $this->execute($task, $state, ['filename' => $this->dir.'/archive1.zip', 'destination' => $this->dir.'/destination1']);
        $output2 = $this->execute($task, $state, ['filename' => $this->dir.'/archive2.zip', 'destination' => $this->dir.'/destination2']);

        self::assertSame($this->dir.'/destination1', $output1);
        self::assertSame($this->dir.'/destination2', $output2);
        self::assertFileExists($this->dir.'/destination1/file1.txt');
        self::assertFileExists($this->dir.'/destination2/file2.txt');
        self::assertFileDoesNotExist($this->dir.'/destination1/file2.txt');
    }

    public function testExtractFailure(): void
    {
        file_put_contents($this->dir.'/file.txt', 'not a directory');
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/file.txt',
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('A \RuntimeException should have been thrown');
        } catch (\RuntimeException $e) {
            self::assertStringStartsWith("Unable to extract file {$this->dir}/archive1.zip to {$this->dir}/file.txt: ZipArchive::extractTo(", $e->getMessage());
        }
        self::assertNull($state->getOutput());
    }

    public function testOpenFailureGivesTheErrorCode(): void
    {
        file_put_contents($this->dir.'/file.txt', 'not a zip archive');
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/file.txt',
            'destination' => $this->dir.'/destination',
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('A \RuntimeException should have been thrown');
        } catch (\RuntimeException $e) {
            self::assertSame("Unable to open file {$this->dir}/file.txt with code ".\ZipArchive::ER_NOZIP, $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{UnzipTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('unzip', UnzipTask::class, $options));

        $task = new UnzipTask();
        $task->initialize($state);

        return [$task, $state];
    }

    private function execute(UnzipTask $task, ProcessState $state, mixed $input): mixed
    {
        $state->reset(false);
        $state->setInput($input);
        $task->execute($state);

        return $state->getOutput();
    }

    private function createArchive(string $filename, string $entry): void
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($filename, \ZipArchive::CREATE));
        $zip->addFromString($entry, 'content of '.$entry);
        $zip->close();
    }
}
