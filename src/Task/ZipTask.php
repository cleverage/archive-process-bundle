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

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Zip files into a given filename.
 */
class ZipTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        if (null === $state->getInput()) {
            $state->setInput([]);
        }
        /**
         * @var array{filename: string, files: array<string>|string, files_base_path: string} $options
         */
        $options = $this->getOptions($state);

        $zip = new \ZipArchive();
        $ret = $zip->open($options['filename'], \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if (true !== $ret) {
            throw new \RuntimeException("Fail to open file {$options['filename']} with code {$ret}");
        }

        $files = $options['files'];
        if (\is_string($files)) {
            $files = [$files];
        }
        $basePath = rtrim($options['files_base_path'], \DIRECTORY_SEPARATOR);
        foreach ($files as $file) {
            if ('' === $options['files_base_path']) {
                // No base path: the file is read at the given path (absolute or relative to the current directory)
                $currentFilename = ltrim($file, \DIRECTORY_SEPARATOR);
                $currentFilepath = $file;
            } else {
                // Only the leading base path is removed, other paths are relative to the base path
                if (str_starts_with($file, $basePath.\DIRECTORY_SEPARATOR)) {
                    $file = substr($file, \strlen($basePath) + 1);
                }
                $currentFilename = ltrim($file, \DIRECTORY_SEPARATOR);
                $currentFilepath = $basePath.\DIRECTORY_SEPARATOR.$currentFilename;
            }
            if (!file_exists($currentFilepath)) {
                throw new \UnexpectedValueException("File does not exists: '{$currentFilepath}'");
            }
            if (!is_readable($currentFilepath)) {
                throw new \UnexpectedValueException("File is not readable: '{$currentFilepath}'");
            }
            if (false === $zip->addFile($currentFilepath, $currentFilename)) {
                throw new \RuntimeException("Unable to add file {$currentFilepath} to zip");
            }
        }

        // The archive is written on close: the PHP warning is replaced by the exception below
        if (!@$zip->close()) {
            throw new \RuntimeException("Unable to write zip file {$options['filename']}: {$zip->getStatusString()}");
        }

        $state->setOutput($options['filename']);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['filename', 'files', 'files_base_path']);
        $resolver->setAllowedTypes('filename', ['string']);
        $resolver->setAllowedTypes('files', ['string', 'array']);
        $resolver->setAllowedTypes('files_base_path', ['string']);
        $resolver->setDefaults([
            'files_base_path' => '',
        ]);
    }

    /**
     * @return array{filename: string, files: array<string>|string, files_base_path: string}|null
     */
    #[\Override]
    protected function getOptions(ProcessState $state): ?array
    {
        // The options depend on the input: resolve them on every execution
        if (\is_array($state->getInput())) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);
            $this->options = $resolver->resolve(array_merge($state->getContextualizedOptions() ?? [], $state->getInput()));
        }

        // @phpstan-ignore return.type
        return $this->options;
    }
}
