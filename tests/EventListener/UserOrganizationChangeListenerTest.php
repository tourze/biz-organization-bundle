<?php

namespace BizOrganizationBundle\Tests\EventListener;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use BizOrganizationBundle\EventListener\UserOrganizationChangeListener;
use BizOrganizationBundle\Repository\UserOrganizationChangeLogRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(UserOrganizationChangeListener::class)]
#[RunTestsInSeparateProcesses]
final class UserOrganizationChangeListenerTest extends AbstractIntegrationTestCase
{
    private UserOrganizationChangeLogRepository $changeLogRepository;

    protected function onSetUp(): void
    {
        $this->changeLogRepository = self::getService(UserOrganizationChangeLogRepository::class);

        // 不清空数据fixture，只清理EntityManager缓存以确保测试隔离
        // 这样可以保持AbstractRepositoryTestCase要求的数据fixture存在
        self::getEntityManager()->clear();
    }

    public function testPostPersistCreatesChangeLog(): void
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');
        $organization = $this->createTestOrganization();

        $userOrganization = $this->createUserOrganization($user, $organization, false);
        self::getEntityManager()->persist($userOrganization);
        self::getEntityManager()->flush();

        $changeLogs = $this->changeLogRepository->findBy(['user' => $user]);
        $this->assertCount(1, $changeLogs);

        $changeLog = $changeLogs[0];
        $this->assertSame($user, $changeLog->getUser());
        $this->assertSame($organization, $changeLog->getOrganization());
        $this->assertStringContainsString('加入组织', $changeLog->getContent() ?? '');
        $this->assertStringContainsString($user->getUserIdentifier(), $changeLog->getContent() ?? '');
        $this->assertStringContainsString($organization->getName(), $changeLog->getContent() ?? '');
    }

    public function testPostUpdateCreatesChangeLogForOrganizationChange(): void
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');
        $oldOrganization = $this->createTestOrganization('Old Org');
        $newOrganization = $this->createTestOrganization('New Org');

        $userOrganization = $this->createUserOrganization($user, $oldOrganization, false);
        self::getEntityManager()->persist($userOrganization);
        self::getEntityManager()->flush();

        $this->clearUserChangeLogs($user);

        $userOrganization->setOrganization($newOrganization);
        self::getEntityManager()->flush();

        $changeLogs = $this->changeLogRepository->findBy(['user' => $user]);
        $this->assertCount(1, $changeLogs);

        $changeLog = $changeLogs[0];
        $this->assertSame($user, $changeLog->getUser());
        $this->assertSame($newOrganization, $changeLog->getOrganization());
        $this->assertSame($newOrganization, $changeLog->getNewOrganization());
        $this->assertStringContainsString('组织从', $changeLog->getContent() ?? '');
        $this->assertStringContainsString('Old Org', $changeLog->getContent() ?? '');
        $this->assertStringContainsString('New Org', $changeLog->getContent() ?? '');
    }

    public function testPostUpdateCreatesChangeLogForPrimaryStatusChange(): void
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');
        $organization = $this->createTestOrganization();

        $userOrganization = $this->createUserOrganization($user, $organization, false);
        self::getEntityManager()->persist($userOrganization);
        self::getEntityManager()->flush();

        $this->clearUserChangeLogs($user);

        $userOrganization->setPrimary(true);
        self::getEntityManager()->flush();

        $changeLogs = $this->changeLogRepository->findBy(['user' => $user]);
        $this->assertCount(1, $changeLogs);

        $changeLog = $changeLogs[0];
        $this->assertStringContainsString('主要组织状态变更为 是', $changeLog->getContent() ?? '');
    }

    public function testPreUpdateStoresChangeSetCorrectly(): void
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');
        $oldOrganization = $this->createTestOrganization('Old Org');
        $newOrganization = $this->createTestOrganization('New Org');

        // 创建用户组织关系
        $userOrganization = $this->createUserOrganization($user, $oldOrganization, false);
        self::getEntityManager()->persist($userOrganization);
        self::getEntityManager()->flush();

        $this->clearUserChangeLogs($user);

        // 模拟 preUpdate 和 postUpdate 的调用过程
        // 这个测试确保 preUpdate 正确存储了变更集合，postUpdate 能够正确使用
        $userOrganization->setOrganization($newOrganization);
        $userOrganization->setPrimary(true);
        self::getEntityManager()->flush();

        // 验证变更日志被正确创建，说明 preUpdate 正确存储了变更集合
        $changeLogs = $this->changeLogRepository->findBy(['user' => $user]);
        $this->assertCount(1, $changeLogs);

        $changeLog = $changeLogs[0];
        $content = $changeLog->getContent() ?? '';

        // 验证两个变更都被记录（组织变更和主要状态变更）
        $this->assertStringContainsString('组织从', $content);
        $this->assertStringContainsString('Old Org', $content);
        $this->assertStringContainsString('New Org', $content);
        $this->assertStringContainsString('主要组织状态变更为 是', $content);

        // 验证新组织被正确设置
        $this->assertSame($newOrganization, $changeLog->getNewOrganization());
    }

    public function testPostRemoveCreatesChangeLog(): void
    {
        $user = $this->createNormalUser('test' . uniqid() . '@example.com', 'password123');
        $organization = $this->createTestOrganization();

        $userOrganization = $this->createUserOrganization($user, $organization);
        self::getEntityManager()->persist($userOrganization);
        self::getEntityManager()->flush();

        $this->clearUserChangeLogs($user);

        self::getEntityManager()->remove($userOrganization);
        self::getEntityManager()->flush();

        $changeLogs = $this->changeLogRepository->findBy(['user' => $user]);
        $this->assertCount(1, $changeLogs);

        $changeLog = $changeLogs[0];
        $this->assertSame($user, $changeLog->getUser());
        $this->assertSame($organization, $changeLog->getOrganization());
        $this->assertStringContainsString('离开组织', $changeLog->getContent() ?? '');
        $this->assertStringContainsString($user->getUserIdentifier(), $changeLog->getContent() ?? '');
        $this->assertStringContainsString($organization->getName(), $changeLog->getContent() ?? '');
    }

    private function createTestOrganization(?string $name = null): Organization
    {
        $organization = new Organization();
        $organization->setName($name ?? 'Test Organization ' . uniqid());
        $organization->setCode('CODE' . uniqid());
        $organization->setValid(true);

        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        return $organization;
    }

    private function createUserOrganization(UserInterface $user, Organization $organization, ?bool $isPrimary = null): UserOrganization
    {
        $userOrganization = new UserOrganization();
        $userOrganization->setUser($user);
        $userOrganization->setOrganization($organization);

        if (null !== $isPrimary) {
            $userOrganization->setPrimary($isPrimary);
        }

        return $userOrganization;
    }

    private function clearUserChangeLogs(UserInterface $user): void
    {
        $this->changeLogRepository->createQueryBuilder('cl')
            ->delete()
            ->where('cl.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }
}
