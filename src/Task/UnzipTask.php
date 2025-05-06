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
 * Unzip a file, requires the destination path in options.
 */
class UnzipTask extends AbstractConfigurableTask
{
    public function execute(ProcessState $state): void
    {
        if (null === $state->getInput()) {
            $state->setInput([]);
        }
        /**
         * @var array{filename: string, destination: string} $options
         */
        $options = $this->getOptions($state);
        $filename = $options['filename'];

        if (!file_exists($filename)) {
            throw new \UnexpectedValueException("File does not exists: '{$filename}'");
        }

        if (!is_readable($filename)) {
            throw new \UnexpectedValueException("File is not readable: '{$filename}'");
        }

        $dest = $options['destination'];

        $zipArchive = new \ZipArchive();
        $res = $zipArchive->open($filename);
        if (true === $res) {
            $zipArchive->extractTo($dest);
            $zipArchive->close();
        } else {
            throw new \RuntimeException("Unable to open file {$filename}");
        }

        $state->setOutput($dest);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['filename', 'destination']);
        $resolver->setAllowedTypes('filename', ['string']);
        $resolver->setAllowedTypes('destination', ['string']);
    }

    /**
     * @return array{filename: string, destination: string}|null
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
