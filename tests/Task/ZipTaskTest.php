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

use CleverAge\ArchiveProcessBundle\Task\ZipTask;
use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[CoversClass(ZipTask::class)]
class ZipTaskTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/'.uniqid('zip_task_test_', true);
        mkdir($this->dir);
        file_put_contents($this->dir.'/file1.txt', 'file 1');
        file_put_contents($this->dir.'/file2.txt', 'file 2');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testZipFiles(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => [$this->dir.'/file1.txt', 'file2.txt'],
            'files_base_path' => $this->dir,
        ]);

        self::assertSame($this->dir.'/archive.zip', $this->execute($task, $state, null));
        self::assertSame(['file1.txt', 'file2.txt'], $this->getEntries($this->dir.'/archive.zip'));
    }

    public function testEachInputIsUsed(): void
    {
        [$task, $state] = $this->createTask(['files_base_path' => $this->dir]);

        $output1 = $this->execute($task, $state, ['filename' => $this->dir.'/archive1.zip', 'files' => 'file1.txt']);
        $output2 = $this->execute($task, $state, ['filename' => $this->dir.'/archive2.zip', 'files' => 'file2.txt']);

        self::assertSame($this->dir.'/archive1.zip', $output1);
        self::assertSame($this->dir.'/archive2.zip', $output2);
        self::assertSame(['file1.txt'], $this->getEntries($this->dir.'/archive1.zip'));
        self::assertSame(['file2.txt'], $this->getEntries($this->dir.'/archive2.zip'));
    }

    public function testOnlyTheLeadingBasePathIsRemoved(): void
    {
        mkdir($this->dir.'/sub'.$this->dir, 0o777, true);
        file_put_contents($this->dir.'/sub'.$this->dir.'/file3.txt', 'file 3');
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => $this->dir.'/sub'.$this->dir.'/file3.txt',
            'files_base_path' => $this->dir.'/',
        ]);

        $this->execute($task, $state, null);

        self::assertSame(['sub'.$this->dir.'/file3.txt'], $this->getEntries($this->dir.'/archive.zip'));
    }

    public function testRelativePathWithoutBasePath(): void
    {
        $cwd = (string) getcwd();
        chdir($this->dir);
        try {
            [$task, $state] = $this->createTask([
                'filename' => $this->dir.'/archive.zip',
                'files' => ['file1.txt', $this->dir.'/file2.txt'],
            ]);

            $this->execute($task, $state, null);
        } finally {
            chdir($cwd);
        }

        self::assertSame(['file1.txt', ltrim($this->dir, '/').'/file2.txt'], $this->getEntries($this->dir.'/archive.zip'));
    }

    public function testWriteFailure(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/missing_directory/archive.zip',
            'files' => 'file1.txt',
            'files_base_path' => $this->dir,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('A \RuntimeException should have been thrown');
        } catch (\RuntimeException $e) {
            self::assertStringStartsWith("Unable to write zip file {$this->dir}/missing_directory/archive.zip: Failure to create temporary file", $e->getMessage());
        }
        self::assertNull($state->getOutput());
    }

    public function testNonArrayInput(): void
    {
        [$task, $state] = $this->createTask([]);

        try {
            $this->execute($task, $state, 'file.zip');
            self::fail('An \UnexpectedValueException should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame('ZipTask expects an array or null input, string given', $e->getMessage());
        }
    }

    public function testSingleFileAsString(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => 'file1.txt',
            'files_base_path' => $this->dir,
        ]);

        $this->execute($task, $state, null);

        self::assertSame(['file1.txt'], $this->getEntries($this->dir.'/archive.zip'));
    }

    public function testInputOverridesConfiguredOptions(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/configured.zip',
            'files' => 'file1.txt',
            'files_base_path' => $this->dir,
        ]);

        self::assertSame($this->dir.'/input.zip', $this->execute($task, $state, ['filename' => $this->dir.'/input.zip']));
        self::assertSame(['file1.txt'], $this->getEntries($this->dir.'/input.zip'));
        self::assertFileDoesNotExist($this->dir.'/configured.zip');
    }

    public function testExistingArchiveIsOverwritten(): void
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($this->dir.'/archive.zip', \ZipArchive::CREATE));
        $zip->addFromString('old.txt', 'old');
        $zip->close();
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => 'file1.txt',
            'files_base_path' => $this->dir,
        ]);

        $this->execute($task, $state, null);

        self::assertSame(['file1.txt'], $this->getEntries($this->dir.'/archive.zip'));
    }

    public function testMissingFile(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => 'missing.txt',
            'files_base_path' => $this->dir,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('An \UnexpectedValueException should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame("File does not exists: '{$this->dir}/missing.txt'", $e->getMessage());
        }
        self::assertNull($state->getOutput());
    }

    public function testUnreadableFile(): void
    {
        chmod($this->dir.'/file1.txt', 0o000);
        if (is_readable($this->dir.'/file1.txt')) {
            self::markTestSkipped('Files are always readable by root');
        }
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => 'file1.txt',
            'files_base_path' => $this->dir,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('An \UnexpectedValueException should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame("File is not readable: '{$this->dir}/file1.txt'", $e->getMessage());
        }
    }

    public function testDirectoryCannotBeAdded(): void
    {
        mkdir($this->dir.'/sub');
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => 'sub',
            'files_base_path' => $this->dir,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->execute($task, $state, null);
    }

    public function testOpenFailure(): void
    {
        mkdir($this->dir.'/archive.zip');
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive.zip',
            'files' => 'file1.txt',
            'files_base_path' => $this->dir,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('A \RuntimeException should have been thrown');
        } catch (\RuntimeException $e) {
            self::assertStringStartsWith("Fail to open file {$this->dir}/archive.zip with code ", $e->getMessage());
        }
    }

    public function testOptionsAreValidatedOnExecution(): void
    {
        // No exception at initialization: the options may come from the input
        [$task, $state] = $this->createTask([]);

        $this->expectException(MissingOptionsException::class);
        $this->execute($task, $state, ['files' => 'file1.txt']);
    }

    public function testUndefinedOptionInInput(): void
    {
        [$task, $state] = $this->createTask(['filename' => $this->dir.'/archive.zip', 'files' => 'file1.txt']);

        $this->expectException(UndefinedOptionsException::class);
        $this->execute($task, $state, ['unknown' => 'value']);
    }

    public function testInvalidOptionType(): void
    {
        [$task, $state] = $this->createTask(['filename' => $this->dir.'/archive.zip']);

        $this->expectException(InvalidOptionsException::class);
        $this->execute($task, $state, ['files' => 12]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{ZipTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('zip', ZipTask::class, $options));

        $task = new ZipTask();
        $task->initialize($state);

        return [$task, $state];
    }

    private function execute(ZipTask $task, ProcessState $state, mixed $input): mixed
    {
        $state->reset(false);
        $state->setInput($input);
        $task->execute($state);

        return $state->getOutput();
    }

    /**
     * @return list<string>
     */
    private function getEntries(string $filename): array
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($filename));
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $entries[] = (string) $zip->getNameIndex($i);
        }
        $zip->close();

        return $entries;
    }
}
