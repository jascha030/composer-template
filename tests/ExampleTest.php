<?php

/*
 * This file is part of the jascha030/template package.
 *
 * (c) Jascha van Aalst <contact@jaschavanaalst.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Jascha030\Project;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Example::class)]
final class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        self::assertSame('Hello, world!', new Example()->hello());
    }
}
