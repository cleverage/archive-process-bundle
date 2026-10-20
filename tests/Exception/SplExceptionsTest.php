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

namespace CleverAge\ArchiveProcessBundle\Tests\Exception;

use CleverAge\ArchiveProcessBundle\Exception\RuntimeException;
use CleverAge\ArchiveProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Exception\ProcessExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The exceptions thrown by the bundle can be caught as ProcessExceptionInterface, and still as the SPL exceptions they
 * were before.
 */
#[CoversClass(UnexpectedValueException::class)]
#[CoversClass(RuntimeException::class)]
class SplExceptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<\Throwable>, class-string<\Throwable>}>
     */
    public static function provideExceptions(): iterable
    {
        yield 'UnexpectedValueException' => [UnexpectedValueException::class, \UnexpectedValueException::class];
        yield 'RuntimeException' => [RuntimeException::class, \RuntimeException::class];
    }

    /**
     * @param class-string<\Throwable> $class
     * @param class-string<\Throwable> $splClass
     */
    #[DataProvider('provideExceptions')]
    public function testExtendsTheSplExceptionAndImplementsTheInterface(string $class, string $splClass): void
    {
        $exception = new $class('message', 12);

        self::assertInstanceOf($splClass, $exception);
        self::assertInstanceOf(ProcessExceptionInterface::class, $exception);
        self::assertSame(['message', 12], [$exception->getMessage(), $exception->getCode()]);
    }
}
