<?php

namespace BizOrganizationBundle\Tests\Repository;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use BizOrganizationBundle\Repository\UserOrganizationChangeLogRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(UserOrganizationChangeLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class UserOrganizationChangeLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置即可
    }

    protected function getRepository(): UserOrganizationChangeLogRepository
    {
        return self::getService(UserOrganizationChangeLogRepository::class);
    }

    protected function createNewEntity(): object
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setCode('TEST');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        $changeLog = new UserOrganizationChangeLog();
        $changeLog->setUser($user);
        $changeLog->setOrganization($organization);
        $changeLog->setContent('User organization change test');

        return $changeLog;
    }

    public function testFindByUser(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('test-user@example.com', 'password123');

        // 创建测试组织
        $organization = new Organization();
        $organization->setName('Test Organization for User');
        $organization->setCode('TEST_USER');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        // 为用户创建多个变动记录
        $changeLog1 = new UserOrganizationChangeLog();
        $changeLog1->setUser($user);
        $changeLog1->setOrganization($organization);
        $changeLog1->setContent('First organization change');
        $repository->save($changeLog1);

        $changeLog2 = new UserOrganizationChangeLog();
        $changeLog2->setUser($user);
        $changeLog2->setOrganization($organization);
        $changeLog2->setContent('Second organization change');
        $repository->save($changeLog2);

        // 为其他用户创建记录（不应该出现在结果中）
        $otherUser = $this->createNormalUser('other-user@example.com', 'password123');
        $otherChangeLog = new UserOrganizationChangeLog();
        $otherChangeLog->setUser($otherUser);
        $otherChangeLog->setOrganization($organization);
        $otherChangeLog->setContent('Other user change');
        $repository->save($otherChangeLog);

        // 测试 findByUser 方法
        $results = $repository->findByUser($user);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);

        foreach ($results as $changeLog) {
            $this->assertInstanceOf(UserOrganizationChangeLog::class, $changeLog);
            $this->assertSame($user, $changeLog->getUser());
        }
    }

    public function testFindByUserWithNoResults(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('no-changes@example.com', 'password123');

        $results = $repository->findByUser($user);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testRepositorySave(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('save-test@example.com', 'password123');

        $organization = new Organization();
        $organization->setName('Save Test Organization');
        $organization->setCode('SAVE_TEST');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        $changeLog = new UserOrganizationChangeLog();
        $changeLog->setUser($user);
        $changeLog->setOrganization($organization);
        $changeLog->setContent('Save test content');

        $repository->save($changeLog);

        $this->assertNotNull($changeLog->getId());

        // 验证可以从数据库中找到保存的记录
        $found = $repository->find($changeLog->getId());
        $this->assertInstanceOf(UserOrganizationChangeLog::class, $found);
        $this->assertSame('Save test content', $found->getContent());
    }

    public function testRepositorySaveWithoutFlush(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('no-flush@example.com', 'password123');

        $organization = new Organization();
        $organization->setName('No Flush Organization');
        $organization->setCode('NO_FLUSH');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        $changeLog = new UserOrganizationChangeLog();
        $changeLog->setUser($user);
        $changeLog->setOrganization($organization);
        $changeLog->setContent('No flush test content');

        $repository->save($changeLog, false);

        // 由于没有flush，ID应该还是null
        $this->assertNull($changeLog->getId());

        // 手动flush后应该有ID
        self::getEntityManager()->flush();
        $this->assertNotNull($changeLog->getId());
    }

    public function testRepositoryRemove(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('remove-test@example.com', 'password123');

        $organization = new Organization();
        $organization->setName('Remove Test Organization');
        $organization->setCode('REMOVE_TEST');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        $changeLog = new UserOrganizationChangeLog();
        $changeLog->setUser($user);
        $changeLog->setOrganization($organization);
        $changeLog->setContent('Remove test content');

        $repository->save($changeLog);
        $changeLogId = $changeLog->getId();
        $this->assertNotNull($changeLogId);

        // 删除记录
        $repository->remove($changeLog);

        // 验证记录已被删除
        $found = $repository->find($changeLogId);
        $this->assertNull($found);
    }

    public function testRepositoryRemoveWithoutFlush(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('remove-no-flush@example.com', 'password123');

        $organization = new Organization();
        $organization->setName('Remove No Flush Organization');
        $organization->setCode('REMOVE_NO_FLUSH');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        $changeLog = new UserOrganizationChangeLog();
        $changeLog->setUser($user);
        $changeLog->setOrganization($organization);
        $changeLog->setContent('Remove no flush test content');

        $repository->save($changeLog);
        $changeLogId = $changeLog->getId();
        $this->assertNotNull($changeLogId);

        // 删除记录但不flush
        $repository->remove($changeLog, false);

        // 由于没有flush，记录应该还存在
        $found = $repository->find($changeLogId);
        $this->assertInstanceOf(UserOrganizationChangeLog::class, $found);

        // 手动flush后记录应该被删除
        self::getEntityManager()->flush();
        $found = $repository->find($changeLogId);
        $this->assertNull($found);
    }
}
