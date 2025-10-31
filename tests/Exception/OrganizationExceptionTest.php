<?php

namespace BizOrganizationBundle\Tests\Exception;

use BizOrganizationBundle\Exception\OrganizationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OrganizationException::class)]
final class OrganizationExceptionTest extends AbstractExceptionTestCase
{
    public function testCannotSetSelfAsParent(): void
    {
        $exception = OrganizationException::cannotSetSelfAsParent();

        $this->assertInstanceOf(OrganizationException::class, $exception);
        $this->assertSame('组织不能设置自己为父组织', $exception->getMessage());
    }

    public function testCannotDeleteWithChildren(): void
    {
        $exception = OrganizationException::cannotDeleteWithChildren();

        $this->assertInstanceOf(OrganizationException::class, $exception);
        $this->assertSame('不能删除有子组织的组织，请先删除子组织或使用强制删除', $exception->getMessage());
    }

    public function testCircularReferenceNotAllowed(): void
    {
        $exception = OrganizationException::circularReferenceNotAllowed();

        $this->assertInstanceOf(OrganizationException::class, $exception);
        $this->assertSame('不能将组织设置为其子组织的父组织，这会形成循环引用', $exception->getMessage());
    }
}
