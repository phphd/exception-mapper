<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag;

use LogicException;
use PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Throwable;

/**
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\AutoTag\ExceptionMatchFormatterTagAutoconfigurationCompilerPass
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition
 * @covers \PhPhD\ExceptionMapper\Mapping\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericName
 *
 * @internal
 */
final class ExceptionMatchFormatterTagAutoconfigurationCompilerPassUnitTest extends TestCase
{
    private ExceptionMatchFormatterTagAutoconfigurationCompilerPass $compilerPass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compilerPass = new ExceptionMatchFormatterTagAutoconfigurationCompilerPass();
    }

    public function testFormatterDefinitionRegisteredUnderQualifiedFormatterClassNameIsTagged(): void
    {
        $formatterDefinition = $this->formatterDefinition();

        $container = new ContainerBuilder();
        $container->setDefinition(ExceptionMatchFormatter::class.'<'.Throwable::class.',string>', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[]],
        ], $formatterDefinition->getTags());
    }

    public function testFormatterDefinitionAliasedUnderQualifiedFormatterClassNameIsTagged(): void
    {
        $formatterDefinition = $this->formatterDefinition();

        $container = new ContainerBuilder();
        $container->setDefinition('formatter', $formatterDefinition);
        $container->setAlias('formatter_alias', 'formatter');
        $container->setAlias(ExceptionMatchFormatter::class.'<'.Throwable::class.',string>', 'formatter_alias');

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[]],
        ], $formatterDefinition->getTags());
    }

    public function testSourceFormatterDefinitionTagAttributesAreCopied(): void
    {
        $formatterDefinition = $this->formatterDefinition([
            'id' => 'custom',
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition(ExceptionMatchFormatter::class.'<'.Throwable::class.',string>', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[
                'id' => 'custom',
            ]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[
                'id' => 'custom',
            ]],
        ], $formatterDefinition->getTags());
    }

    public function testPreExistingQualifiedFormatterTagIsNotOverwritten(): void
    {
        $formatterDefinition = $this->formatterDefinition([
            'id' => 'custom',
        ]);
        $formatterDefinition->addTag(ExceptionMatchFormatter::class.'<'.Throwable::class.',string>', [
            'priority' => 10,
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition(ExceptionMatchFormatter::class.'<'.Throwable::class.',string>', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[
                'id' => 'custom',
            ]],
            ExceptionMatchFormatter::class.'<'.Throwable::class.',string>' => [[
                'priority' => 10,
            ]],
        ], $formatterDefinition->getTags());
    }

    public function testNonAutoconfiguredFormatterIsNotTagged(): void
    {
        $formatterDefinition = $this->formatterDefinition(isAutoconfigured: false);

        $container = new ContainerBuilder();
        $container->setDefinition(ExceptionMatchFormatter::class.'<'.Throwable::class.',string>', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[]],
        ], $formatterDefinition->getTags());
    }

    public function testFormatterDefinitionRegisteredUnderUnqualifiedBaseClassNameIdIsSkipped(): void
    {
        $formatterDefinition = $this->formatterDefinition();

        $container = new ContainerBuilder();
        $container->setDefinition(ExceptionMatchFormatter::class, $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[]],
        ], $formatterDefinition->getTags());
    }

    public function testFormatterDefinitionRegisteredUnderBaseClassNamePrefixedIdIsSkipped(): void
    {
        $formatterDefinition = $this->formatterDefinition();

        $container = new ContainerBuilder();
        $container->setDefinition(ExceptionMatchFormatter::class.'Factory', $formatterDefinition);

        $this->compilerPass->process($container);

        self::assertSame([
            ExceptionMatchFormatter::class => [[]],
        ], $formatterDefinition->getTags());
    }

    /** @dataProvider malformedGenericFormatterServiceClassNamesIds */
    public function testMalformedGenericFormatterServiceClassNameIdRegisteredDefinitionIsReported(string $malformedGenericFormatterId): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition($malformedGenericFormatterId, $this->formatterDefinition());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Generic type "'.$malformedGenericFormatterId.'" must match "'.ExceptionMatchFormatter::class.'<TException,TResult>" format.');

        $this->compilerPass->process($container);
    }

    /** @return iterable<string,array{string}> */
    public static function malformedGenericFormatterServiceClassNamesIds(): iterable
    {
        yield 'one argument' => [ExceptionMatchFormatter::class.'<string>'];

        yield 'three arguments' => [ExceptionMatchFormatter::class.'<'.Throwable::class.',string,string>'];

        yield 'empty argument' => [ExceptionMatchFormatter::class.'<'.Throwable::class.',>'];

        yield 'nested argument' => [ExceptionMatchFormatter::class.'<'.Throwable::class.',list<string>>'];

        yield 'whitespace' => [ExceptionMatchFormatter::class.'<'.Throwable::class.', string>'];

        yield 'unclosed' => [ExceptionMatchFormatter::class.'<'.Throwable::class.',string'];
    }

    /** @param array<string,string> $tagAttributes */
    private function formatterDefinition(array $tagAttributes = [], bool $isAutoconfigured = true): Definition
    {
        $definition = new Definition(ExceptionMatchFormatter::class);
        $definition
            ->setAutoconfigured(autoconfigured: $isAutoconfigured)
            ->addTag(ExceptionMatchFormatter::class, $tagAttributes)
        ;

        return $definition;
    }
}
