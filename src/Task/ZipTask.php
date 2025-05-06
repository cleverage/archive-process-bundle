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
        foreach ($files as $file) {
            $currentFilename = ltrim(str_replace($options['files_base_path'], '', $file), \DIRECTORY_SEPARATOR);
            $currentFilepath = $options['files_base_path'].\DIRECTORY_SEPARATOR.$currentFilename;
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

        $zip->close();

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
    protected function getOptions(ProcessState $state): ?array
    {
        if (null === $this->options && \is_array($state->getInput())) {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);
            $this->options = $resolver->resolve(array_merge($state->getContextualizedOptions() ?? [], $state->getInput()));
        }

        // @phpstan-ignore return.type
        return $this->options;
    }
}
