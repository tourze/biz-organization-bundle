<?php

declare(strict_types=1);

namespace BizOrganizationBundle\Tests;

use BizOrganizationBundle\BizOrganizationBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(BizOrganizationBundle::class)]
#[RunTestsInSeparateProcesses]
final class BizOrganizationBundleTest extends AbstractBundleTestCase
{
}
