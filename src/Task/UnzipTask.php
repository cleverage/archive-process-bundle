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

namespace CleverAge\ArchiveProcessBundle\Task;

use CleverAge\ArchiveProcessBundle\Exception\RuntimeException;
use CleverAge\ArchiveProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Unzip a file, requires the destination path in options.
 *
 * @final since 2.3
 */
class UnzipTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        if (null === $state->getInput()) {
            $state->setInput([]);
        }
        if (!\is_array($state->getInput())) {
            throw new UnexpectedValueException(\sprintf('UnzipTask expects an array or null input, %s given', get_debug_type($state->getInput())));
        }
        /**
         * @var array{filename: string, destination: string, max_files: ?int, max_size: ?int} $options
         */
        $options = $this->getOptions($state);
        $filename = $options['filename'];

        if (!file_exists($filename)) {
            throw new UnexpectedValueException("File does not exists: '{$filename}'");
        }

        if (!is_readable($filename)) {
            throw new UnexpectedValueException("File is not readable: '{$filename}'");
        }

        $dest = $options['destination'];

        $zipArchive = new \ZipArchive();
        $res = $zipArchive->open($filename);
        if (true !== $res) {
            throw new RuntimeException("Unable to open file {$filename} with code {$res}");
        }

        try {
            $this->checkLimits($zipArchive, $filename, $options['max_files'], $options['max_size']);
        } catch (RuntimeException $e) {
            $zipArchive->close();

            throw $e;
        }

        // The PHP warning is replaced by the exception below
        error_clear_last();
        $extracted = @$zipArchive->extractTo($dest);
        $error = error_get_last()['message'] ?? $zipArchive->getStatusString();
        $zipArchive->close();
        if (!$extracted) {
            throw new RuntimeException("Unable to extract file {$filename} to {$dest}: {$error}");
        }

        $state->setOutput($dest);
    }

    /**
     * Checks the archive against the limits before extracting anything (protection against zip bombs): the number of
     * entries and their uncompressed sizes are read from the central directory of the archive.
     */
    protected function checkLimits(\ZipArchive $zipArchive, string $filename, ?int $maxFiles, ?int $maxSize): void
    {
        if (null !== $maxFiles && $zipArchive->numFiles > $maxFiles) {
            throw new RuntimeException("Unable to extract file {$filename}: it contains {$zipArchive->numFiles} entries, more than the max_files option ({$maxFiles})");
        }
        if (null === $maxSize) {
            return;
        }
        $size = 0;
        for ($index = 0; $index < $zipArchive->numFiles; ++$index) {
            $stat = $zipArchive->statIndex($index);
            if (false === $stat) {
                throw new RuntimeException("Unable to extract file {$filename}: the entry {$index} cannot be read");
            }
            $size += $stat['size'];
            if ($size > $maxSize) {
                throw new RuntimeException("Unable to extract file {$filename}: its uncompressed size is more than the max_size option ({$maxSize} bytes)");
            }
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['filename', 'destination']);
        $resolver->setAllowedTypes('filename', ['string']);
        $resolver->setAllowedTypes('destination', ['string']);
        $resolver->setDefaults([
            'max_files' => null,
            'max_size' => null,
        ]);
        $resolver->setAllowedTypes('max_files', ['null', 'int']);
        $resolver->setAllowedTypes('max_size', ['null', 'int']);
    }

    /**
     * @return array{filename: string, destination: string, max_files: ?int, max_size: ?int}|null
     */
    #[\Override]
    protected function getOptions(ProcessState $state): ?array
    {
        // The options depend on the input: resolve them on every execution
        if (\is_array($state->getInput())) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);
            /** @var array<string, mixed> $options */
            $options = $resolver->resolve(array_merge($state->getContextualizedOptions() ?? [], $state->getInput()));
            $this->options = $options;
        }

        // @phpstan-ignore return.type
        return $this->options;
    }
}
