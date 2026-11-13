<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Throwable;

/**
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize\ExceptionMatchFormatterTagMaterializationCompilerPass
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericName
 *
 * @internal
 */
final class ExceptionMatchFormatterTagMaterializationCompilerPassUnitTest extends TestCase
{
    private ExceptionMatchFormatterTagMaterializationCompilerPass $compilerPass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compilerPass = new ExceptionMatchFormatterTagMaterializationCompilerPass();
    }

    public function testQualifiedFormatterTagIsMaterializedIntoAResultTypeQualifiedFormatterTag(): void
    {
        $formatterDefinition = $this->formatterDefinition($initialTags = [
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame($initialTags + [
            ExceptionMatchFormatter::class.'<?,string>' => [[]],
        ], $formatterDefinition->getTags());
    }

    public function testQualifiedFormatterTagsMaterializeIntoAOneTypeQualifiedTagPerResultType(): void
    {
        $formatterDefinition = $this->formatterDefinition($initialTags = [
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[]],
            ExceptionMatchFormatter::class.'<'.LogicException::class.',string>' => [[]],
            ExceptionMatchFormatter::class.'<'.RuntimeException::class.',int>' => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame($initialTags + [
            ExceptionMatchFormatter::class.'<?,string>' => [[]],
            ExceptionMatchFormatter::class.'<?,int>' => [[]],
        ], $formatterDefinition->getTags());
    }

    public function testQualifiedFormatterTagAttributesAreCopied(): void
    {
        $formatterDefinition = $this->formatterDefinition($initialTags = [
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [$attributes = [
                'id' => 'custom',
            ]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame($initialTags + [
            ExceptionMatchFormatter::class.'<?,string>' => [$attributes],
        ], $formatterDefinition->getTags());
    }

    public function testMalformedQualifiedFormatterTagIsRejected(): void
    {
        $formatterDefinition = $this->formatterDefinition([
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<string>' => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Generic type "'.ExceptionMatchFormatter::class.'<string>" must match "'.ExceptionMatchFormatter::class.'<TException,TResult>" format.');

        $this->compilerPass->process($container);
    }

    public function testFormatterDefinitionWithoutQualifiedFormatterTagIsRejected(): void
    {
        $formatterDefinition = $this->formatterDefinition([
            ExceptionMatchFormatter::class => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Formatter service "formatter" does not specify generic arguments. Define them in #[AsAlias(ExceptionMatchFormatter::class.\'<TException,TResult>\')], specifying your TException and TResult.');

        $this->compilerPass->process($container);
    }

    /** @param array<string,list<array<string,string>>> $tags */
    private function formatterDefinition(array $tags = []): Definition
    {
        $definition = new Definition(ExceptionMatchFormatter::class);

        $definition->setTags($tags);

        return $definition;
    }
}
