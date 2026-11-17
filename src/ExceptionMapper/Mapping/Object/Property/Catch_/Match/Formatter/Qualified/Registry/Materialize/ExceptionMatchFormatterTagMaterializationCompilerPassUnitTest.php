<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericName;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericNameUnion;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Throwable;

use function array_map;

/**
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\Materialize\ExceptionMatchFormatterTagMaterializationCompilerPass
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericName
 *
 * @internal
 */
final class ExceptionMatchFormatterTagMaterializationCompilerPassUnitTest extends TestCase
{
    private ExceptionMatchFormatterTagMaterializationCompilerPass $materializationPass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->materializationPass = new ExceptionMatchFormatterTagMaterializationCompilerPass();
    }

    public function testQualifiedFormatterTagIsMaterializedIntoAResultTypeQualifiedFormatterTag(): void
    {
        $formatterDefinition = $this->formatterDefinition($initialTags = [
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->materializationPass->process($container);

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

        $this->materializationPass->process($container);

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

        $this->materializationPass->process($container);

        self::assertSame($initialTags + [
            ExceptionMatchFormatter::class.'<?,string>' => [$attributes],
        ], $formatterDefinition->getTags());
    }

    public function testFormatterClassGenericNameUnionServiceIsRegistered(): void
    {
        $formatterDefinition = $this->formatterDefinition([
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.LogicException::class.',string>' => [[]],
            ExceptionMatchFormatter::class.'<'.RuntimeException::class.',int>' => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->materializationPass->process($container);

        $formatterClassNameUnionDefinition = $container->getDefinition('formatter.formatterClassNameUnion');

        self::assertSame(ExceptionMatchFormatterClassGenericNameUnion::class, $formatterClassNameUnionDefinition->getClass());
        self::assertFalse($formatterClassNameUnionDefinition->isPublic());

        /** @var list<Definition> $classNameDefinitions */
        [$classNameDefinitions] = $formatterClassNameUnionDefinition->getArguments();

        self::assertSame([
            [ExceptionMatchFormatterClassGenericName::class, [LogicException::class, 'string']],
            [ExceptionMatchFormatterClassGenericName::class, [RuntimeException::class, 'int']],
        ], array_map(static fn(Definition $definition): array => [$definition->getClass(), $definition->getArguments()], $classNameDefinitions));

        self::assertSame([
            ExceptionMatchFormatterClassGenericNameUnion::class => [[
                'id' => 'formatter',
            ]],
        ], $formatterClassNameUnionDefinition->getTags());
    }

    public function testFormatterClassGenericNameUnionServiceIsTaggedWithExplicitlyProvidedFormatterId(): void
    {
        $formatterDefinition = $this->formatterDefinition([
            ExceptionMatchFormatter::class => [[
                'id' => 'custom',
            ]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[]],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);

        $this->materializationPass->process($container);

        self::assertSame([
            ExceptionMatchFormatterClassGenericNameUnion::class => [[
                'id' => 'custom',
            ]],
        ], $container->getDefinition('formatter.formatterClassNameUnion')->getTags());
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

        $this->materializationPass->process($container);
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

        $this->materializationPass->process($container);
    }

    /** @param array<string,list<array<string,string>>> $tags */
    private function formatterDefinition(array $tags = []): Definition
    {
        $definition = new Definition(ExceptionMatchFormatter::class);

        $definition->setTags($tags);

        return $definition;
    }
}
