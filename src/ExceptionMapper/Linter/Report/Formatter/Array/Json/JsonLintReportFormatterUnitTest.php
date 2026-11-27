<?php

declare(strict_types=1);

namespace PhPhD\ExceptionMapper\Linter\Report\Formatter\Array\Json;

use PhPhD\ExceptionMapper\Connector\Symfony\PhdExceptionMapperExtension;
use PhPhD\ExceptionMapper\Connector\Symfony\Tests\PublicServiceCompilerPass;
use PhPhD\ExceptionMapper\Linter\Port\LintMappingUseCase;
use PhPhD\ExceptionMapper\Tests\Unit\Stub\MessageWithNoTryAttribute;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhPhD\ExceptionMapper\Linter\Report\Formatter\Array\Json\JsonLintReportFormatter
 * @covers \PhPhD\ExceptionMapper\Linter\Report\Formatter\Array\ArrayLintReportFormatter
 * @covers \PhPhD\ExceptionMapper\Linter\Report\Defect\Location\DefectLocation
 */
final class JsonLintReportFormatterUnitTest extends TestCase
{
    /** @var LintMappingUseCase<class-string,string> */
    private LintMappingUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new PhdExceptionMapperExtension())->getContainer([
            'kernel.environment' => 'test',
            'kernel.build_dir' => __DIR__.'/var',
        ]);

        $container->addCompilerPass(new PublicServiceCompilerPass(LintMappingUseCase::class));

        $container->compile();

        /** @var LintMappingUseCase<class-string,string> $useCase */
        $useCase = $container->get(LintMappingUseCase::class);
        $this->useCase = $useCase;
    }

    public function testDefectIsFormattedAsJson(): void
    {
        [$json] = $this->useCase->lint('class-string', [MessageWithNoTryAttribute::class], 'json');

        self::assertSame(
            <<<'JSON'
            {
                "defects": [
                    {
                        "severity": "warning",
                        "class": "PhPhD\\ExceptionMapper\\Tests\\Unit\\Stub\\MessageWithNoTryAttribute",
                        "property": "property",
                        "messages": [
                            "Properties declare #[Catch_] mappings, but the class is not marked with #[Try_] attribute, so it never matches."
                        ]
                    }
                ]
            }
            JSON,
            $json,
        );
    }
}
