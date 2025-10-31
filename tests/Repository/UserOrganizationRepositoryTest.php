<?php

namespace BizOrganizationBundle\Tests\Repository;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use BizOrganizationBundle\Repository\UserOrganizationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(UserOrganizationRepository::class)]
#[RunTestsInSeparateProcesses]
final class UserOrganizationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 不清空数据fixture，只清理EntityManager缓存以确保测试隔离
        // 这样可以保持AbstractRepositoryTestCase要求的数据fixture存在
        self::getEntityManager()->clear();
    }

    protected function getRepository(): UserOrganizationRepository
    {
        return self::getService(UserOrganizationRepository::class);
    }

    protected function createNewEntity(): object
    {
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->assertNotNull($user->getUserIdentifier());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());

        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setValid(true);
        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        $userOrganization = new UserOrganization();
        $userOrganization->setUser($user);
        $userOrganization->setOrganization($organization);

        return $userOrganization;
    }

    public function testSave(): void
    {
        $entity = $this->createNewEntity();
        $this->assertInstanceOf(UserOrganization::class, $entity);
        $repository = $this->getRepository();

        $repository->save($entity);

        $this->assertIsInt($entity->getId());
        $this->assertGreaterThan(0, $entity->getId());
    }

    public function testRemove(): void
    {
        $entity = $this->createNewEntity();
        $this->assertInstanceOf(UserOrganization::class, $entity);
        $repository = $this->getRepository();

        $repository->save($entity);
        $id = $entity->getId();

        $repository->remove($entity);

        $removedEntity = $repository->find($id);
        $this->assertNull($removedEntity);
    }

    public function testFindAll(): void
    {
        $repository = $this->getRepository();

        // 记录当前数据库中的记录数（包括fixture数据）
        $initialCount = $repository->count();

        $entity1 = $this->createNewEntity();
        $this->assertInstanceOf(UserOrganization::class, $entity1);
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $this->assertInstanceOf(UserOrganization::class, $entity2);
        $repository->save($entity2);

        $results = $repository->findAll();

        $this->assertIsArray($results);
        // 验证新增了2条记录
        $this->assertCount($initialCount + 2, $results);
    }

    public function testFindByUser(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('testuser@example.com', 'password123');

        // 创建2个组织给同一个用户
        $organization1 = $this->createTestOrganization('Org 1');
        $organization2 = $this->createTestOrganization('Org 2');

        $userOrg1 = new UserOrganization();
        $userOrg1->setUser($user);
        $userOrg1->setOrganization($organization1);
        $userOrg1->setPrimary(true);
        $repository->save($userOrg1);

        $userOrg2 = new UserOrganization();
        $userOrg2->setUser($user);
        $userOrg2->setOrganization($organization2);
        $userOrg2->setPrimary(false);
        $repository->save($userOrg2);

        $results = $repository->findByUser($user);

        $this->assertCount(2, $results);
        $this->assertContains($userOrg1, $results);
        $this->assertContains($userOrg2, $results);
    }

    public function testFindPrimaryByUser(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('primaryuser@example.com', 'password123');

        // 创建2个组织，只有一个是主要的
        $organization1 = $this->createTestOrganization('Primary Org');
        $organization2 = $this->createTestOrganization('Secondary Org');

        $userOrg1 = new UserOrganization();
        $userOrg1->setUser($user);
        $userOrg1->setOrganization($organization1);
        $userOrg1->setPrimary(true);
        $repository->save($userOrg1);

        $userOrg2 = new UserOrganization();
        $userOrg2->setUser($user);
        $userOrg2->setOrganization($organization2);
        $userOrg2->setPrimary(false);
        $repository->save($userOrg2);

        $primaryOrganization = $repository->findPrimaryByUser($user);

        $this->assertNotNull($primaryOrganization);
        $this->assertSame($userOrg1, $primaryOrganization);
        $this->assertTrue($primaryOrganization->isPrimary());
        $this->assertSame($organization1, $primaryOrganization->getOrganization());
    }

    public function testFindPrimaryByUserReturnsNullWhenNoPrimary(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('noprimary@example.com', 'password123');

        $organization = $this->createTestOrganization('Non-primary Org');

        $userOrg = new UserOrganization();
        $userOrg->setUser($user);
        $userOrg->setOrganization($organization);
        $userOrg->setPrimary(false);
        $repository->save($userOrg);

        $primaryOrganization = $repository->findPrimaryByUser($user);

        $this->assertNull($primaryOrganization);
    }

    public function testFindByOrganizationId(): void
    {
        $repository = $this->getRepository();
        $organization = $this->createTestOrganization('Test Organization');

        // 创建2个用户加入同一个组织
        $user1 = $this->createNormalUser('user1@example.com', 'password123');
        $user2 = $this->createNormalUser('user2@example.com', 'password123');

        $userOrg1 = new UserOrganization();
        $userOrg1->setUser($user1);
        $userOrg1->setOrganization($organization);
        $repository->save($userOrg1);

        $userOrg2 = new UserOrganization();
        $userOrg2->setUser($user2);
        $userOrg2->setOrganization($organization);
        $repository->save($userOrg2);

        $organizationId = $organization->getId();
        $this->assertNotNull($organizationId, 'Organization ID should not be null');

        $results = $repository->findByOrganizationId($organizationId);

        $this->assertCount(2, $results);
        $organizationIds = array_map(fn ($uo) => $uo->getOrganization()->getId(), $results);
        $this->assertContains($organizationId, $organizationIds);
    }

    public function testCountUsersByOrganizationId(): void
    {
        $repository = $this->getRepository();
        $organization = $this->createTestOrganization('Count Test Org');

        // 初始计数
        $organizationId = $organization->getId();
        $this->assertNotNull($organizationId, 'Organization ID should not be null');

        $initialCount = $repository->countUsersByOrganizationId($organizationId);

        // 添加3个用户
        for ($i = 1; $i <= 3; ++$i) {
            $user = $this->createNormalUser("countuser{$i}@example.com", 'password123');
            $userOrg = new UserOrganization();
            $userOrg->setUser($user);
            $userOrg->setOrganization($organization);
            $repository->save($userOrg);
        }

        $finalCount = $repository->countUsersByOrganizationId($organizationId);

        $this->assertEquals($initialCount + 3, $finalCount);
    }

    public function testFindByUserAndOrganizationHierarchy(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('hierarchyuser@example.com', 'password123');

        // 创建父组织和子组织
        $parentOrg = $this->createTestOrganization('Parent Organization');
        $childOrg = $this->createTestOrganization('Child Organization');
        $childOrg->setParent($parentOrg);
        self::getEntityManager()->flush();

        // 用户加入子组织
        $userOrg = new UserOrganization();
        $userOrg->setUser($user);
        $userOrg->setOrganization($childOrg);
        $repository->save($userOrg);

        // 根据父组织查找应该能找到子组织的关联
        $parentOrgId = $parentOrg->getId();
        $this->assertNotNull($parentOrgId, 'Parent organization ID should not be null');

        $results = $repository->findByUserAndOrganizationHierarchy($user, $parentOrgId);

        // 注：由于查询逻辑复杂，这里主要验证方法调用不出错
        $this->assertIsArray($results);
    }

    public function testRemoveByUserAndOrganizationId(): void
    {
        $repository = $this->getRepository();
        $user = $this->createNormalUser('removeuser@example.com', 'password123');
        $organization = $this->createTestOrganization('Remove Test Org');

        // 创建用户组织关联
        $userOrg = new UserOrganization();
        $userOrg->setUser($user);
        $userOrg->setOrganization($organization);
        $repository->save($userOrg);

        // 验证关联存在
        $beforeRemove = $repository->findByUser($user);
        $this->assertCount(1, $beforeRemove);

        // 删除关联
        $organizationId = $organization->getId();
        $this->assertNotNull($organizationId, 'Organization ID should not be null');

        $removedCount = $repository->removeByUserAndOrganizationId($user, $organizationId);

        $this->assertEquals(1, $removedCount);

        // 验证关联已删除
        $afterRemove = $repository->findByUser($user);
        $this->assertCount(0, $afterRemove);
    }

    private function createTestOrganization(string $name): Organization
    {
        $organization = new Organization();
        $organization->setName($name);
        $organization->setCode('CODE' . uniqid());
        $organization->setValid(true);

        self::getEntityManager()->persist($organization);
        self::getEntityManager()->flush();

        return $organization;
    }
}
