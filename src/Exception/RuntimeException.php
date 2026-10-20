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

namespace CleverAge\ArchiveProcessBundle\Exception;

use CleverAge\ProcessBundle\Exception\ProcessExceptionInterface;

/**
 * Thrown by the bundle, can be caught with the ProcessExceptionInterface of the process bundle.
 */
class RuntimeException extends \RuntimeException implements ProcessExceptionInterface
{
}
