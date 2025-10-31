<?php

namespace BizOrganizationBundle\Tests\Entity;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(UserOrganization::class)]
final class UserOrganizationTest extends AbstractEntityTestCase
{
    protected function createEntity(): UserOrganization
    {
        return new UserOrganization();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'primary' => ['primary', true],
            // user 和 organization 是对象属性，在单独的测试方法中处理
        ];
    }

    public function testCanBeInstantiated(): void
    {
        $userOrganization = new UserOrganization();

        $this->assertInstanceOf(UserOrganization::class, $userOrganization);
        $this->assertNull($userOrganization->getId());
        $this->assertFalse($userOrganization->isPrimary());
    }

    public function testSettersAndGetters(): void
    {
        $userOrganization = new UserOrganization();

        // 创建测试用户实现
        /** @phpstan-ignore-next-line staticMethod.dynamicCall */
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('getUserIdentifier')->willReturn('test@example.com');

        // 创建实际的Organization实例
        $organization = new Organization();
        $organization->setName('Test Organization');

        $userOrganization->setUser($user);
        $this->assertSame($user, $userOrganization->getUser());

        $userOrganization->setOrganization($organization);
        $this->assertSame($organization, $userOrganization->getOrganization());

        $userOrganization->setPrimary(true);
        $this->assertTrue($userOrganization->isPrimary());
    }

    public function testToString(): void
    {
        $userOrganization = new UserOrganization();

        // 创建测试用户实现
        /** @phpstan-ignore-next-line staticMethod.dynamicCall */
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('getUserIdentifier')->willReturn('testuser');

        // 创建实际的Organization实例
        $organization = new Organization();
        $organization->setName('Test Organization');

        $userOrganization->setUser($user);
        $userOrganization->setOrganization($organization);

        $result = $userOrganization->__toString();

        $this->assertIsString($result);
        $this->assertStringContainsString('testuser', $result);
        $this->assertStringContainsString('Test Organization', $result);
        $this->assertEquals('User testuser belongs to Organization Test Organization', $result);
    }
}
