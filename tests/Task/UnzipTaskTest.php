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

use CleverAge\ArchiveProcessBundle\Exception\RuntimeException;
use CleverAge\ArchiveProcessBundle\Task\UnzipTask;
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

    public function testNonArrayInput(): void
    {
        [$task, $state] = $this->createTask([]);

        try {
            $this->execute($task, $state, 'file.zip');
            self::fail('An \UnexpectedValueException should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame('UnzipTask expects an array or null input, string given', $e->getMessage());
        }
    }

    public function testInputOverridesConfiguredOptions(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/configured',
        ]);

        self::assertSame($this->dir.'/input', $this->execute($task, $state, ['destination' => $this->dir.'/input']));
        self::assertFileExists($this->dir.'/input/file1.txt');
        self::assertDirectoryDoesNotExist($this->dir.'/configured');
    }

    public function testExistingFilesAreOverwrittenOtherFilesAreKept(): void
    {
        mkdir($this->dir.'/destination');
        file_put_contents($this->dir.'/destination/file1.txt', 'old content');
        file_put_contents($this->dir.'/destination/other.txt', 'other content');
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
        ]);

        $this->execute($task, $state, null);

        self::assertStringEqualsFile($this->dir.'/destination/file1.txt', 'content of file1.txt');
        self::assertStringEqualsFile($this->dir.'/destination/other.txt', 'other content');
    }

    public function testMissingFile(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/missing.zip',
            'destination' => $this->dir.'/destination',
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('An \UnexpectedValueException should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame("File does not exists: '{$this->dir}/missing.zip'", $e->getMessage());
        }
        self::assertNull($state->getOutput());
    }

    public function testUnreadableFile(): void
    {
        chmod($this->dir.'/archive1.zip', 0o000);
        if (is_readable($this->dir.'/archive1.zip')) {
            self::markTestSkipped('Files are always readable by root');
        }
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('An \UnexpectedValueException should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame("File is not readable: '{$this->dir}/archive1.zip'", $e->getMessage());
        }
    }

    public function testOptionsAreValidatedOnExecution(): void
    {
        // No exception at initialization: the options may come from the input
        [$task, $state] = $this->createTask([]);

        $this->expectException(MissingOptionsException::class);
        $this->execute($task, $state, ['filename' => $this->dir.'/archive1.zip']);
    }

    public function testUndefinedOptionInInput(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
        ]);

        $this->expectException(UndefinedOptionsException::class);
        $this->execute($task, $state, ['unknown' => 'value']);
    }

    public function testInvalidOptionType(): void
    {
        [$task, $state] = $this->createTask(['filename' => $this->dir.'/archive1.zip']);

        $this->expectException(InvalidOptionsException::class);
        $this->execute($task, $state, ['destination' => ['not', 'a', 'string']]);
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

    public function testMaxFilesIsChecked(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
            'max_files' => 0,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('The extraction should have failed');
        } catch (RuntimeException $e) {
            self::assertSame("Unable to extract file {$this->dir}/archive1.zip: it contains 1 entries, more than the max_files option (0)", $e->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->dir.'/destination');
    }

    public function testMaxSizeIsChecked(): void
    {
        // "content of file1.txt": 20 bytes
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
            'max_size' => 19,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('The extraction should have failed');
        } catch (RuntimeException $e) {
            self::assertSame("Unable to extract file {$this->dir}/archive1.zip: its uncompressed size is more than the max_size option (19 bytes)", $e->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->dir.'/destination');
    }

    public function testArchiveWithinTheLimitsIsExtracted(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
            'max_files' => 1,
            'max_size' => 20,
        ]);

        self::assertSame($this->dir.'/destination', $this->execute($task, $state, null));
        self::assertFileExists($this->dir.'/destination/file1.txt');
    }

    public function testMaxSizeIsCheckedOnTheDecompressedBytes(): void
    {
        // 100 000 bytes declared as 10 bytes in the archive
        $this->createForgedArchive($this->dir.'/forged.zip', 100000, 10);
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/forged.zip',
            'destination' => $this->dir.'/destination',
            'max_size' => 1000,
        ]);

        try {
            $this->execute($task, $state, null);
            self::fail('The extraction should have failed');
        } catch (RuntimeException $e) {
            self::assertSame("Unable to extract file {$this->dir}/forged.zip: its uncompressed size is more than the max_size option (1000 bytes)", $e->getMessage());
        }
        self::assertDirectoryDoesNotExist($this->dir.'/destination');
    }

    public function testMaxSizeIsCheckedOnTheWholeArchive(): void
    {
        // Two entries of 20 bytes: 40 bytes in all
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($this->dir.'/archive1.zip'));
        $zip->addFromString('file3.txt', 'content of file3.txt');
        $zip->close();
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
            'max_size' => 39,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('its uncompressed size is more than the max_size option (39 bytes)');

        $this->execute($task, $state, null);
    }

    public function testLimitsInTheInputAreDeprecated(): void
    {
        [$task, $state] = $this->createTask([
            'filename' => $this->dir.'/archive1.zip',
            'destination' => $this->dir.'/destination',
            'max_size' => 19,
        ]);

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            // Still applied until 3.0
            self::assertSame($this->dir.'/destination', $this->execute($task, $state, ['max_size' => null, 'max_files' => 1]));
        } finally {
            restore_error_handler();
        }

        self::assertSame([
            'Since cleverage/archive-process-bundle 2.5: The "max_files" key of the input of the "archive_unzip" task (UnzipTask) is deprecated: set the limits in the task options only, an input coming from the processed data must not disable them. It will be ignored in 3.0.',
            'Since cleverage/archive-process-bundle 2.5: The "max_size" key of the input of the "archive_unzip" task (UnzipTask) is deprecated: set the limits in the task options only, an input coming from the processed data must not disable them. It will be ignored in 3.0.',
        ], $deprecations);
    }

    /**
     * An archive whose entry declares a wrong uncompressed size, in its local header and in the central directory.
     */
    private function createForgedArchive(string $filename, int $size, int $declaredSize): void
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($filename, \ZipArchive::CREATE));
        $zip->addFromString('bomb.txt', str_repeat('a', $size));
        $zip->close();

        $content = (string) file_get_contents($filename);
        $declared = pack('V', $declaredSize);
        // Local file header: uncompressed size at offset 22
        $local = strpos($content, "PK\x03\x04");
        self::assertIsInt($local);
        $content = substr_replace($content, $declared, $local + 22, 4);
        // Central directory file header: uncompressed size at offset 24
        $central = strpos($content, "PK\x01\x02");
        self::assertIsInt($central);
        $content = substr_replace($content, $declared, $central + 24, 4);
        file_put_contents($filename, $content);
    }

    private function createArchive(string $filename, string $entry): void
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($filename, \ZipArchive::CREATE));
        $zip->addFromString($entry, 'content of '.$entry);
        $zip->close();
    }
}
