<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Tests;

use Composer\InstalledVersions;
use PHPat\Selector\ClassNamespace;
use PHPat\Selector\Modifier\AllOfSelectorModifier;
use PHPat\Selector\Modifier\AnyOfSelectorModifier;
use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\BuildStep;
use PHPat\Test\PHPat;
use PhPhD\ExceptionMapper\Blueprint\ExceptionMappingBlueprint;
use PhPhD\ExceptionMapper\ExceptionMapper;
use PhPhD\ExceptionMapper\ExceptionMappingNode;
use PhPhD\ExceptionMapper\FaultTolerant\FaultTolerantExceptionMapper;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcher;
use PhPhD\ExceptionMapper\Matcher\ExceptionMatcherIteratorAggregate;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\CompilingObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Blueprint\Registry\ObjectExceptionMappingBlueprintRegistry;
use PhPhD\ExceptionMapper\Object\Property\Catch_;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Autoload\ConstantsAutoloadingClassDiscovery;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Autoload\ConstantsClassLoader;
use PhPhD\ExceptionMapper\Object\Property\Catch_\CatchExceptionMappingNode;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionBlueprint;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Blueprint\MatchConditionCompiler;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\MatchCondition;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\Value\ValueException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\ExceptionMatch;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\ExceptionMatchFormatter;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericName;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericNameUnion;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterServiceDefinition;
use PhPhD\ExceptionMapper\Object\Property\Path\PropertyPath;
use PhPhD\ExceptionMapper\Object\Try_;
use PhPhD\ExceptionMapper\Util\Log\SpyLogger;
use PhPhD\ExceptionToolkit\Unwrapper\ExceptionUnwrapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Uid\Exception\InvalidArgumentException as InvalidUidException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * @api
 */
final class ArchitectureRuleSet
{
    #[TestRule]
    public function testCompilerDependencies(): BuildStep
    {
        return $this->layerRule('compiler');
    }

    #[TestRule]
    public function testBlueprintDependencies(): BuildStep
    {
        return $this->layerRule('blueprint');
    }

    #[TestRule]
    public function testNodeDependencies(): BuildStep
    {
        return $this->layerRule('node');
    }

    #[TestRule]
    public function testMatchConditionDependencies(): BuildStep
    {
        return $this->layerRule('matchCondition');
    }

    #[TestRule]
    public function testMatchConditionBlueprintDependencies(): BuildStep
    {
        return $this->layerRule('matchConditionBlueprint');
    }

    #[TestRule]
    public function testMatchConditionCompilerDependencies(): BuildStep
    {
        return $this->layerRule('matchConditionCompiler');
    }

    #[TestRule]
    public function testExceptionMatchDependencies(): BuildStep
    {
        return $this->layerRule('exceptionMatch');
    }

    #[TestRule]
    public function testExceptionMatchFormatterDependencies(): BuildStep
    {
        return $this->layerRule('exceptionMatchFormatter');
    }

    #[TestRule]
    public function testValidatorExceptionMatchFormatterDependencies(): BuildStep
    {
        return $this->layerRule('validatorExceptionMatchFormatter');
    }

    #[TestRule]
    public function testLinterDependencies(): BuildStep
    {
        return $this->layerRule('linter');
    }

    #[TestRule]
    public function testMatchMapperDependencies(): BuildStep
    {
        return $this->layerRule('matchMapper');
    }

    #[TestRule]
    public function testValidatorMapperDependencies(): BuildStep
    {
        return $this->layerRule('validatorMapper');
    }

    #[TestRule]
    public function testValidatorMiddlewareDependencies(): BuildStep
    {
        return $this->layerRule('validatorMiddleware');
    }

    #[TestRule]
    public function testMessengerValidatorMiddlewareDependencies(): BuildStep
    {
        return $this->layerRule('messengerValidatorMiddleware');
    }

    #[TestRule]
    public function testSymfonyConnectorDependencies(): BuildStep
    {
        return $this->layerRule('symfonyConnector');
    }

    #[TestRule]
    public function testFaultTolerantMapperDependencies(): BuildStep
    {
        return $this->layerRule('faultTolerantMapper');
    }

    #[TestRule]
    public function testPropertyTypeDependencies(): BuildStep
    {
        return $this->layerRule('propertyType');
    }

    public function layerRule(string $name): BuildStep
    {
        $layers = $this->layers();

        $layer = $layers[$name];

        /** @var SelectorInterface $layerClassesSelector */
        $layerClassesSelector = $this->{$name}(); // @phpstan-ignore method.dynamicName

        $deps = [$layerClassesSelector, ...$layer['deps']];

        if (isset($layer['wraps'])) {
            $wrappedLayerNames = $layer['wraps'];

            foreach ($wrappedLayerNames as $wrappedLayerName) {
                /** @var SelectorInterface $wrappedLayerSelector */
                $wrappedLayerSelector = $this->{$wrappedLayerName}(); // @phpstan-ignore method.dynamicName

                $wrappedLayerDeps = $layers[$wrappedLayerName]['deps'];

                $deps = [...$deps, $wrappedLayerSelector, ...$wrappedLayerDeps];
            }
        }

        $deps = [...$deps, self::coreDeps()];

        return PHPat::rule()
            ->classes(Selector::AllOf(
                $layerClassesSelector,
                Selector::Not(Selector::classname('/\\\Tests\\\/', regex: true)),
                Selector::Not(Selector::extends(TestCase::class)),
            ))
            ->canOnly()
            ->dependOn()
            ->classes(...$deps)
            ->because($layer['description'] ?? 'See its dependency rules in '.self::class.'::layers()')
        ;
    }

    public static function coreDeps(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::classname(Assert::class),
            Selector::inNamespace('PhPhD\ExceptionMapper\Util\Iterator'),
        );
    }

    /** @return array<string,array{deps:list<SelectorInterface>, wraps?: list<string>, description?: string}> */
    public function layers(): array
    {
        return [
            'symfonyConnector' => [
                'deps' => [
                    Selector::inNamespace('PhPhD\ExceptionToolkit'),

                    Selector::classname(ConstantsClassLoader::class),
                    Selector::classname(MatchConditionCompiler::class),
                    Selector::classname(ExceptionMatchFormatter::class),
                    Selector::classname(ExceptionMatchFormatterClassGenericName::class),
                    Selector::classname(ExceptionMatchFormatterClassGenericNameUnion::class),
                    Selector::classname(FaultTolerantExceptionMapper::class),
                    // Bundle & Extension
                    Selector::inNamespace('Symfony\Component'),
                    Selector::classname(InstalledVersions::class),
                ],
                'description' => 'Container wiring must not reach into the mapping model',
            ],
            'faultTolerantMapper' => [
                'deps' => [
                    Selector::classname(ExceptionMapper::class),
                    Selector::inNamespace('Psr'),
                ],
                'description' => 'Fault tolerance must stay independent of the mapping model',
            ],
            'linter' => [
                'deps' => [
                    $this->compiler(),
                    Selector::classname(SpyLogger::class),
                    $this->blueprint(),
                    $this->matchConditionBlueprint(),
                    $this->matchCondition(),
                    Selector::inNamespace('Psr'),
                    Selector::inNamespace('Composer\ClassMapGenerator'),
                    Selector::inNamespace('Symfony\Component\Console'),
                ],
            ],
            'messengerValidatorMiddleware' => [
                'wraps' => ['validatorMiddleware', 'validatorMapper'],
                'deps' => [
                    Selector::inNamespace('Symfony\Component\Messenger'),
                ],
            ],
            'validatorMiddleware' => [
                'deps' => [
                    Selector::inNamespace('Symfony\Component\Validator'),
                ],
            ],
            'validatorMapper' => [
                'deps' => [
                    $this->matchMapper(),
                    $this->node(),
                    $this->exceptionMatch(),
                    Selector::inNamespace('Symfony\Component\Validator'),
                ],
            ],
            'matchMapper' => [
                'deps' => [
                    $this->blueprint(),
                    $this->node(),
                    $this->exceptionMatch(),
                    Selector::classname(ExceptionUnwrapper::class),
                ],
            ],
            'compiler' => [
                'wraps' => ['blueprint', 'node'],
                'deps' => [
                    $this->propertyType(),
                    $this->matchConditionCompiler(),
                    $this->exceptionMatchFormatter(),
                    Selector::inNamespace('Psr'),
                ],
            ],
            'blueprint' => [
                'wraps' => ['node'],
                'deps' => [
                    $this->propertyType(),
                    $this->matchConditionBlueprint(),
                ],
            ],
            'node' => [
                'deps' => [
                    $this->blueprint(),
                    $this->exceptionMatch(),
                    $this->matchCondition(),
                ],
                'description' => 'Mapping nodes are the core model layer and must depend on almost nothing',
            ],
            'propertyType' => [
                'deps' => [],
                'description' => 'Property type analysis must be independent',
            ],
            'matchConditionCompiler' => [
                'wraps' => ['matchConditionBlueprint', 'matchCondition'],
                'deps' => [
                    $this->propertyType(),
                    Selector::inNamespace('Psr'),
                ],
            ],
            'matchConditionBlueprint' => [
                'wraps' => ['matchCondition'],
                'deps' => [
                    $this->propertyType(),
                ],
            ],
            'matchCondition' => [
                'deps' => [
                    $this->node(),
                    $this->exceptionMatch(),
                ],
            ],
            'validatorExceptionMatchFormatter' => [
                'wraps' => ['exceptionMatchFormatter'],
                'deps' => [
                    Selector::inNamespace('Symfony\Component\Validator'),
                ],
            ],
            'exceptionMatchFormatter' => [
                'deps' => [
                    Selector::classname(ExceptionMatch::class),
                    Selector::classname(TranslatorInterface::class),
                    Selector::inNamespace('Psr\Container'), // Registry
                ],
            ],
            'exceptionMatch' => [
                'deps' => [
                    Selector::classname(CatchExceptionMappingNode::class),
                ],
                'description' => 'Exception match models must not depend on anything else',
            ],
        ];
    }

    /** @psalm-suppress UnusedMethod */
    public function symfonyConnector(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\Connector\Symfony'),
            Selector::implements(CompilerPassInterface::class),
            Selector::classname(ConstantsAutoloadingClassDiscovery::class),
            Selector::implements(ConstantsAutoloadingClassDiscovery::class),
            Selector::classname(ExceptionMatchFormatterServiceDefinition::class),
        );
    }

    public function node(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::classname(ExceptionMatcher::class),
            Selector::implements(ExceptionMatcher::class),
            Selector::classname(ExceptionMappingNode::class),
            Selector::implements(ExceptionMappingNode::class),
            Selector::classname(ExceptionMatcherIteratorAggregate::class),
            Selector::implements(ExceptionMatcherIteratorAggregate::class),
            Selector::classname(PropertyPath::class),
        );
    }

    public function blueprint(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::classname(ExceptionMappingBlueprint::class),
            Selector::implements(ExceptionMappingBlueprint::class),
            Selector::classname(ObjectExceptionMappingBlueprintRegistry::class),
        );
    }

    public function compiler(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::AnyOf(
                Selector::inNamespace('/\\\Blueprint\\\(Registry\\\)?Compiler/', regex: true),
                Selector::classname(Try_::class),
                Selector::classname(Catch_::class),
                Selector::classname(CompilingObjectExceptionMappingBlueprintRegistry::class),
            ),
            Selector::Not($this->symfonyConnector()),
        );
    }

    public function matchConditionCompiler(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::classname(MatchConditionCompiler::class),
            Selector::implements(MatchConditionCompiler::class),
            Selector::classname(Catch_::class),
        );
    }

    public function matchCondition(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::classname(MatchCondition::class),
            Selector::implements(MatchCondition::class),
            // Contract
            Selector::classname(ValueException::class),
            // Third-party
            Selector::classname(ValidationFailedException::class),
            Selector::classname(InvalidUidException::class),
        );
    }

    public function matchConditionBlueprint(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::classname(MatchConditionBlueprint::class),
            Selector::AllOf(
                Selector::implements(MatchConditionBlueprint::class),
                Selector::Not($this->matchConditionCompiler()),
            ),
        );
    }

    public function exceptionMatchFormatter(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter'),
            Selector::Not($this->validatorExceptionMatchFormatter()),
            Selector::Not($this->symfonyConnector()),
        );
    }

    public function validatorExceptionMatchFormatter(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator'),
            Selector::Not($this->symfonyConnector()),
        );
    }

    public function exceptionMatch(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::AllOf(
                Selector::inNamespace($namespace = 'PhPhD\ExceptionMapper\Object\Property\Catch_\Match'),
                Selector::Not(Selector::inNamespace($namespace.'\Condition')),
                Selector::Not(Selector::inNamespace($namespace.'\Formatter')),
                Selector::Not(Selector::inNamespace($namespace.'\Port')),
            ),
            Selector::classname(ExceptionMatchFormatter::class),
        );
    }

    public function linter(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\Linter'),
            Selector::Not($this->symfonyConnector()),
        );
    }

    public function matchMapper(): AnyOfSelectorModifier
    {
        return Selector::AnyOf(
            Selector::AllOf(
                Selector::inNamespace($namespace = 'PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port'),
                Selector::Not(Selector::inNamespace($namespace.'\Symfony')),
            ),
            Selector::classname(ExceptionMapper::class),
        );
    }

    public function validatorMapper(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator'),
            Selector::Not(Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware')),
        );
    }

    public function validatorMiddleware(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware'),
            Selector::Not(Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Messenger')),
        );
    }

    /** @psalm-suppress UnusedMethod */
    public function faultTolerantMapper(): AllOfSelectorModifier
    {
        return Selector::AllOf(
            Selector::inNamespace('PhPhD\ExceptionMapper\FaultTolerant'),
            Selector::Not($this->symfonyConnector()),
        );
    }

    public function propertyType(): ClassNamespace
    {
        return Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Type');
    }

    /** @psalm-suppress UnusedMethod */
    public function messengerValidatorMiddleware(): ClassNamespace
    {
        return Selector::inNamespace('PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Port\Symfony\Validator\Middleware\Messenger');
    }
}
