<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Tests;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\PublicServiceCompilerPass;
use PhPhD\ExceptionMapper\Linter\MappingLinter;
use PhPhD\ExceptionMapper\Linter\Report\Defect\Severity\DefectSeverity;
use PhPhD\ExceptionMapper\Linter\Report\LintReport;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\AbstractTryMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\ChildOfPrivateCatchMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid\IncompatibleFormatterMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid\LazilyDiscoveredUnknownMatchConditionMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid\MissingEnumFromConditionMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\Invalid\UndefinedConstantConditionMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\ParentPrivateCatchMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\TryWithNoCatchAttributesMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\TryWithNonMatchableObjectMessage;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\UnregisteredFormatter;
use PhPhD\ExceptionMapper\Linter\Tests\Stub\UnregisteredFormatterMessage;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Exception\CatchAttributeInstantiationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Exception\CatchExceptionMappingPrintCompilationFailedException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\ViolationsEmbeddedException;
use PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Symfony\Validator\Embedded\ViolationsEmbeddedExceptionFormatter;
use PhPhD\ExceptionMapper\Object\Property\Iterable\Tests\Stub\RootObject;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\HandleableMessageStub;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\MessageWithNoTryAttribute;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\NestedHandleableMessage;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\NestedItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @internal
 *
 * @covers \PhPhD\ExceptionMapper\Linter\Class\ClassMappingLinter
 * @covers \PhPhD\ExceptionMapper\Linter\Class\CompilationDefectCollector
 * @covers \PhPhD\ExceptionMapper\Util\Log\SpyLogger
 * @covers \PhPhD\ExceptionMapper\Linter\Report\LintReport
 * @covers \PhPhD\ExceptionMapper\Linter\Report\Class\ClassReport
 * @covers \PhPhD\ExceptionMapper\Linter\Report\Defect\MappingDefect
 * @covers \PhPhD\ExceptionMapper\Linter\Report\Defect\Location\DefectLocation
 * @covers \PhPhD\ExceptionMapper\Object\Blueprint\ObjectExceptionMappingBlueprint
 * @covers \PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\ObjectExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Blueprint\Registry\Compiler\Exception\ObjectExceptionMappingPrintCompilationFailedException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Blueprint\PropertyExceptionMappingBlueprint
 * @covers \PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\PropertyExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Property\Blueprint\Compiler\Exception\PropertyExceptionMappingPrintCompilationFailedException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\CatchExceptionMappingBlueprint
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\CatchExceptionMappingCompiler
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Exception\CatchAttributeInstantiationFailedException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Blueprint\Compiler\Exception\CatchExceptionMappingPrintCompilationFailedException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\ExceptionMatchQualifiedFormatter
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\UnregisteredExceptionMatchFormatterException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterDoesNotTakeExceptionTypeException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericName
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\ExceptionMatchFormatterClassGenericNameUnion
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Formatter\Qualified\Registry\GenericParameterDoesNotTakeTypeException
 * @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Composite\CompositeMatchConditionBlueprint
 * @covers \PhPhD\ExceptionMapper\Util\Iterator\MemoizingIteratorAggregate
 */
final class MappingLinterUnitTest extends TestCase
{
    /** @var MappingLinter<class-string, LintReport> */
    private MappingLinter $linter;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new PhdExceptionMapperExtension())->getContainer([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__.'/var',
        ]);

        $container->addCompilerPass(new PublicServiceCompilerPass(MappingLinter::class.'<class-string,'.LintReport::class.'>'));

        $container->compile();

        /** @var MappingLinter<class-string,LintReport> $linter */
        $linter = $container->get(MappingLinter::class.'<class-string,'.LintReport::class.'>');

        $this->linter = $linter;
    }

    public function testTryAttributePresentButCatchAttributesMissingIsReported(): void
    {
        [$classReport] = $this->linter->lint([TryWithNoCatchAttributesMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(TryWithNoCatchAttributesMessage::class, $defect->getLocation()->toReference());
        self::assertSame([
            '#[Try_] class does not define any #[Catch_] mappings and no nested matchable properties, so it never matches anything.',
            'Class exception mapping compilation has failed.',
        ], $defect->getMessages());
    }

    public function testCatchAttributesPresentButTryAttributeMissingIsReported(): void
    {
        [$classReport] = $this->linter->lint([MessageWithNoTryAttribute::class])->getClassReports();

        [$defect] = $classReport->getDefects();
        self::assertCount(1, $classReport->getDefects());

        self::assertSame(DefectSeverity::Warning, $defect->getSeverity());
        self::assertSame(MessageWithNoTryAttribute::class.'::$property', $defect->getLocation()->toReference());
        self::assertSame([
            'Properties declare #[Catch_] mappings, but the class is not marked with #[Try_], so it never matches anything.',
        ], $defect->getMessages());
    }

    public function testReportsAbstractTryClass(): void
    {
        [$classReport] = $this->linter->lint([AbstractTryMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame([
            '#[Try_] on an abstract class has no effect: the attribute is not inherited by the subclasses.',
            'Class exception mapping compilation has failed.',
        ], $defect->getMessages());
    }

    public function testReportsTryClassWhoseNestedObjectCannotCarryABlueprint(): void
    {
        [$classReport] = $this->linter->lint([TryWithNonMatchableObjectMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(TryWithNonMatchableObjectMessage::class, $defect->getLocation()->toReference());
        self::assertSame([
            '#[Try_] class does not define any #[Catch_] mappings and no nested matchable properties, so it never matches anything.',
            'Class exception mapping compilation has failed.',
        ], $defect->getMessages());
    }

    public function testReportsParentPrivateCatchProperties(): void
    {
        [$classReport] = $this->linter->lint([ChildOfPrivateCatchMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Warning, $defect->getSeverity());
        self::assertSame(ChildOfPrivateCatchMessage::class.'::$parentDivisor', $defect->getLocation()->toReference());
        self::assertSame([
            'Private property '.ParentPrivateCatchMessage::class.'::$parentDivisor declares #[Catch_] mappings that are invisible to '.ChildOfPrivateCatchMessage::class.'.',
        ], $defect->getMessages());
    }

    public function testReportsUnregisteredFormatter(): void
    {
        [$classReport] = $this->linter->lint([UnregisteredFormatterMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(UnregisteredFormatterMessage::class.'::$divisor', $defect->getLocation()->toReference());
        self::assertSame([
            'Exception Match Formatter is not registered: "'.UnregisteredFormatter::class.'"',
            '#[Catch_] exception mapping compilation failed.',
        ], $defect->getMessages());
    }

    public function testReportsIncompatibleFormatter(): void
    {
        [$classReport] = $this->linter->lint([IncompatibleFormatterMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(IncompatibleFormatterMessage::class.'::$divisor', $defect->getLocation()->toReference());
        self::assertSame([
            'Generic parameter does not take "DivisionByZeroError" type. Allowed types: '.ViolationsEmbeddedException::class.', '.ValidationFailedException::class.'.',
            'Exception Match Formatter "'.ViolationsEmbeddedExceptionFormatter::class.'" does not take the exception type.',
            '#[Catch_] exception mapping compilation failed.',
        ], $defect->getMessages());
    }

    public function testReportsBrokenMatchConditionMapping(): void
    {
        [$classReport] = $this->linter->lint([MissingEnumFromConditionMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(MissingEnumFromConditionMessage::class.'::$deliveryMethod', $defect->getLocation()->toReference());
        self::assertSame([
            'EnumValueMatchCondition requires `from:` to contain a class-string of a BackedEnum, got: NULL',
            '#[Catch_] exception mapping compilation failed.',
        ], $defect->getMessages());
    }

    public function testReportsUndefinedMatchConstant(): void
    {
        [$classReport] = $this->linter->lint([UndefinedConstantConditionMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(UndefinedConstantConditionMessage::class.'::$divisor', $defect->getLocation()->toReference());

        $cause = $defect->getCause();
        self::assertInstanceOf(CatchExceptionMappingPrintCompilationFailedException::class, $cause);
        self::assertInstanceOf(CatchAttributeInstantiationFailedException::class, $cause->getPrevious());

        self::assertSame([
            'Undefined constant "PhPhD\\ExceptionMapper\\Object\\Property\\Catch_\\Match\\Condition\\Undefined\\undefined_condition"',
            '#[Catch_] attribute instantiation failed.',
            '#[Catch_] exception mapping compilation failed.',
        ], $defect->getMessages());
    }

    /** @covers \PhPhD\ExceptionMapper\Object\Property\Catch_\Match\Condition\Qualified\QualifiedMatchConditionCompiler */
    public function testReportsLazilyCompiledBrokenMatchCondition(): void
    {
        [$classReport] = $this->linter->lint([LazilyDiscoveredUnknownMatchConditionMessage::class])->getClassReports();

        [$defect] = $classReport->getDefects();

        self::assertSame(DefectSeverity::Error, $defect->getSeverity());
        self::assertSame(LazilyDiscoveredUnknownMatchConditionMessage::class.'::$divisor', $defect->getLocation()->toReference());
        self::assertInstanceOf(CatchExceptionMappingPrintCompilationFailedException::class, $defect->getCause());
        self::assertSame([
            'Match condition compiler not found for: unknown_condition',
            '#[Catch_] exception mapping compilation failed.',
        ], $defect->getMessages());
    }

    public function testNoWarningIsReportedOnAValidNestedOnlyMapping(): void
    {
        $defects = $this->linter->lint([RootObject::class])->getClassReports();

        self::assertSame([], $defects);
    }

    public function testNoErrorsAreReportedOnValidMappings(): void
    {
        $report = $this->linter->lint([
            HandleableMessageStub::class,
            NestedHandleableMessage::class,
            NestedItem::class,
        ]);

        self::assertFalse($report->hasDefects());
        self::assertSame([], $report->getClassReports());
    }
}
