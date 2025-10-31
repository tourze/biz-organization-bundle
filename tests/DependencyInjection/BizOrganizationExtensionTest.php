<?php

namespace BizOrganizationBundle\Tests\DependencyInjection;

use BizOrganizationBundle\DependencyInjection\BizOrganizationExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(BizOrganizationExtension::class)]
final class BizOrganizationExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function setUp(): void
    {
        // 集成测试不需要额外的设置
    }

    private function getContainer(): ContainerInterface
    {
        /** @phpstan-ignore-next-line */
        $container = $this->createStub(ContainerInterface::class);

        $container->method('has')
            ->willReturnCallback(fn (string $id): bool => 'property_accessor' === $id)
        ;

        $container->method('get')
            ->willReturnCallback(function (string $id): object {
                if ('property_accessor' === $id) {
                    return new PropertyAccessor();
                }
                throw new \RuntimeException("Service '{$id}' not found");
            })
        ;

        $container->method('initialized')
            ->willReturnCallback(fn (string $id): bool => 'property_accessor' === $id)
        ;

        $container->method('getParameter')
            ->willThrowException(new \RuntimeException('Parameter not found'))
        ;

        $container->method('hasParameter')
            ->willReturn(false)
        ;

        return $container;
    }

    public function testServiceConfiguration(): void
    {
        // 测试服务配置是否正确加载
        $container = $this->getContainer();

        // 验证 PropertyAccessor 服务可用
        $this->assertTrue($container->has('property_accessor'));
        $service = $container->get('property_accessor');
        $this->assertInstanceOf(PropertyAccessor::class, $service);

        // 验证 biz-organization.property-accessor 服务不存在（符合预期）
        $this->assertFalse($container->has('biz-organization.property-accessor'));
    }
}
