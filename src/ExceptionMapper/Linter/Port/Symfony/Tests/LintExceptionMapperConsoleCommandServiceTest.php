<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Port\Symfony\Tests;

use PhPhD\ExceptionMapper\Connector\Symfony\Tests\BundleTestCase;
use PhPhD\ExceptionMapper\Linter\Port\Symfony\LintExceptionMapperConsoleCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\LazyCommand;

/**
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperBundle
 * @covers \PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension
 *
 * @internal
 */
final class LintExceptionMapperConsoleCommandServiceTest extends BundleTestCase
{
    public function testCommandIsRegisteredInConsoleApplication(): void
    {
        $command = (new Application(self::bootKernel()))->find('lint:exception-mapper');

        self::assertInstanceOf(LazyCommand::class, $command);
        self::assertInstanceOf(LintExceptionMapperConsoleCommand::class, $command->getCommand());
    }
}
