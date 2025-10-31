<?php

declare(strict_types=1);

namespace BizOrganizationBundle\Tests;

use BizOrganizationBundle\BizOrganizationBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Tourze\DoctrineResolveTargetEntityBundle\DoctrineResolveTargetEntityBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(BizOrganizationBundle::class)]
#[RunTestsInSeparateProcesses]
final class BizOrganizationBundleTest extends AbstractBundleTestCase
{
    public function testGetBundleDependencies(): void
    {
        $dependencies = BizOrganizationBundle::getBundleDependencies();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey(DoctrineBundle::class, $dependencies);
        $this->assertArrayHasKey(DoctrineResolveTargetEntityBundle::class, $dependencies);
        $this->assertArrayHasKey(DoctrineTimestampBundle::class, $dependencies);
        $this->assertArrayHasKey(EasyAdminBundle::class, $dependencies);
        $this->assertArrayHasKey(TwigBundle::class, $dependencies);

        // 验证所有依赖都启用了 'all' 环境
        foreach ($dependencies as $bundleClass => $config) {
            $this->assertArrayHasKey('all', $config);
            $this->assertTrue($config['all']);
        }
    }
}
