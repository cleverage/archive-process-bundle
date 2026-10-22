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

namespace CleverAge\ArchiveProcessBundle;

use CleverAge\ArchiveProcessBundle\DependencyInjection\CleverAgeArchiveProcessExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final since 2.3
 */
class CleverAgeArchiveProcessBundle extends Bundle
{
    /**
     * The alias of the extension, "cleverage_archive_process", is not the one guessed from the bundle name.
     */
    #[\Override]
    public function getContainerExtension(): ExtensionInterface
    {
        if (!$this->extension instanceof ExtensionInterface) {
            $this->extension = new CleverAgeArchiveProcessExtension();
        }

        return $this->extension;
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
