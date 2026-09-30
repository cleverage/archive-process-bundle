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
