<?php

declare(strict_types=1);

namespace Pds\Skeleton;

final class TestRunner
{
    public const VERSION = 2.0;
    public const PATCH = 0;

    public function execute()
    {
        ComplianceValidatorTest::run();
        PackageGeneratorTest::run();
    }
}
