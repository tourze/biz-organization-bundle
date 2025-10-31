<?php

namespace BizOrganizationBundle\Tests\Entity;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(UserOrganizationChangeLog::class)]
final class UserOrganizationChangeLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): UserOrganizationChangeLog
    {
        return new UserOrganizationChangeLog();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'content' => ['content', 'Test content'],
            // user 和 organization, newOrganization 是对象属性，在单独的测试方法中处理
        ];
    }

    public function testCanBeInstantiated(): void
    {
        $changeLog = new UserOrganizationChangeLog();

        $this->assertInstanceOf(UserOrganizationChangeLog::class, $changeLog);
        $this->assertNull($changeLog->getId());
        $this->assertNull($changeLog->getUser());
        $this->assertNull($changeLog->getOrganization());
        $this->assertNull($changeLog->getNewOrganization());
        $this->assertNull($changeLog->getContent());
    }

    public function testSettersAndGetters(): void
    {
        $changeLog = new UserOrganizationChangeLog();

        // 创建测试用户实现
        /** @phpstan-ignore-next-line staticMethod.dynamicCall */
        $user = $this->createStub(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $user->method('getUserIdentifier')->willReturn('test@example.com');

        // 创建实际的Organization实例
        $organization = new Organization();
        $organization->setName('Original Organization');

        $newOrganization = new Organization();
        $newOrganization->setName('New Organization');

        $content = 'User organization change test';

        $changeLog->setUser($user);
        $this->assertSame($user, $changeLog->getUser());

        $changeLog->setOrganization($organization);
        $this->assertSame($organization, $changeLog->getOrganization());

        $changeLog->setNewOrganization($newOrganization);
        $this->assertSame($newOrganization, $changeLog->getNewOrganization());

        $changeLog->setContent($content);
        $this->assertSame($content, $changeLog->getContent());
    }

    public function testToString(): void
    {
        $changeLog = new UserOrganizationChangeLog();

        $this->assertIsString($changeLog->__toString());
        $this->assertSame('User Unknown User organization change: No description', $changeLog->__toString());
    }
}
