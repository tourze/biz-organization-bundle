<?php

namespace BizOrganizationBundle\Tests\Service;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Exception\OrganizationException;
use BizOrganizationBundle\Repository\OrganizationRepository;
use BizOrganizationBundle\Service\OrganizationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * 组织服务集成测试
 *
 * 测试覆盖：
 * - 组织创建、更新、删除操作
 * - 组织层次结构管理
 * - 组织移动和验证逻辑
 * - 组织查询和统计功能
 * - 异常处理和边界条件
 *
 * @internal
 */
#[CoversClass(OrganizationService::class)]
#[RunTestsInSeparateProcesses]
final class OrganizationServiceTest extends AbstractIntegrationTestCase
{
    private OrganizationService $organizationService;

    private OrganizationRepository $organizationRepository;

    protected function onSetUp(): void
    {
        $this->organizationService = self::getService(OrganizationService::class);
        $this->organizationRepository = self::getService(OrganizationRepository::class);
    }

    /**
     * 测试创建组织的完整流程
     */
    #[Test]
    public function testCreateOrganizationWithCompleteData(): void
    {
        $adminUser = $this->createAdminUser('admin@org-test.com', 'password123');

        $organization = $this->organizationService->createOrganization(
            name: 'Tech Department',
            description: 'Technology and IT operations',
            code: 'TECH001',
            parent: null,
            manager: $adminUser,
            sortOrder: 10
        );

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertSame('Tech Department', $organization->getName());
        $this->assertSame('Technology and IT operations', $organization->getDescription());
        $this->assertSame('TECH001', $organization->getCode());
        $this->assertNull($organization->getParent());
        $this->assertSame($adminUser, $organization->getManager());
        $this->assertSame(10, $organization->getSortNumber());
        $this->assertTrue($organization->isValid());
        $this->assertNotNull($organization->getId());

        // 验证数据已持久化到数据库
        $this->assertEntityPersisted($organization);

        // 从数据库重新获取验证
        $persistedOrg = $this->organizationRepository->find($organization->getId());
        $this->assertNotNull($persistedOrg);
        $this->assertSame('Tech Department', $persistedOrg->getName());
    }

    /**
     * 测试创建最小化组织（仅必填字段）
     */
    #[Test]
    public function testCreateOrganizationWithMinimalData(): void
    {
        $organization = $this->organizationService->createOrganization('HR Department');

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertSame('HR Department', $organization->getName());
        $this->assertNull($organization->getDescription());
        $this->assertNull($organization->getCode());
        $this->assertNull($organization->getParent());
        $this->assertNull($organization->getManager());
        $this->assertSame(0, $organization->getSortNumber());
        $this->assertTrue($organization->isValid());
    }

    /**
     * 测试创建有父组织的子组织
     */
    #[Test]
    public function testCreateChildOrganization(): void
    {
        $parentOrg = $this->organizationService->createOrganization('Parent Corp');
        $childOrg = $this->organizationService->createOrganization(
            name: 'Child Department',
            parent: $parentOrg
        );

        $this->assertSame($parentOrg, $childOrg->getParent());

        // 刷新父组织以获取最新的children集合
        self::getEntityManager()->refresh($parentOrg);

        $this->assertTrue($parentOrg->getChildren()->contains($childOrg));
        $this->assertFalse($parentOrg->isLeaf());
        $this->assertTrue($childOrg->isLeaf());
        $this->assertTrue($parentOrg->isRoot());
        $this->assertFalse($childOrg->isRoot());
    }

    /**
     * 测试组织更新功能
     */
    #[Test]
    public function testUpdateOrganization(): void
    {
        $organization = $this->organizationService->createOrganization('Original Name');
        $manager = $this->createNormalUser('manager@update-test.com', 'password123');

        $updatedOrg = $this->organizationService->updateOrganization(
            organization: $organization,
            name: 'Updated Name',
            description: 'Updated description',
            code: 'UPD001',
            manager: $manager,
            sortOrder: 5,
            valid: true
        );

        $this->assertSame($organization, $updatedOrg);
        $this->assertSame('Updated Name', $updatedOrg->getName());
        $this->assertSame('Updated description', $updatedOrg->getDescription());
        $this->assertSame('UPD001', $updatedOrg->getCode());
        $this->assertSame($manager, $updatedOrg->getManager());
        $this->assertSame(5, $updatedOrg->getSortNumber());
        $this->assertTrue($updatedOrg->isValid());
    }

    /**
     * 测试删除没有子组织的组织
     */
    #[Test]
    public function testDeleteOrganizationWithoutChildren(): void
    {
        $organization = $this->organizationService->createOrganization('To Delete');
        $organizationId = $organization->getId();
        $this->assertNotNull($organizationId, 'Organization ID should not be null after creation');

        $this->organizationService->deleteOrganization($organization);

        $deletedOrg = $this->organizationRepository->find($organizationId);
        $this->assertNull($deletedOrg, '组织应已从数据库中删除');
    }

    /**
     * 测试删除有子组织的组织会抛出异常
     */
    #[Test]
    public function testDeleteOrganizationWithChildrenShouldThrowException(): void
    {
        $parentOrg = $this->organizationService->createOrganization('Parent');
        $childOrg = $this->organizationService->createOrganization('Child', parent: $parentOrg);

        // 刷新实体以确保关系正确加载
        self::getEntityManager()->refresh($parentOrg);

        // 验证父子关系确实存在
        $this->assertFalse($parentOrg->getChildren()->isEmpty(), '父组织应该有子组织');
        $this->assertTrue($parentOrg->getChildren()->contains($childOrg), '父组织应该包含子组织');

        $this->expectException(OrganizationException::class);
        $this->expectExceptionMessage('不能删除有子组织的组织');

        $this->organizationService->deleteOrganization($parentOrg);
    }

    /**
     * 测试强制删除有子组织的组织
     */
    #[Test]
    public function testForceDeleteOrganizationWithChildren(): void
    {
        $parentOrg = $this->organizationService->createOrganization('Parent');
        $childOrg = $this->organizationService->createOrganization('Child', parent: $parentOrg);
        $grandchildOrg = $this->organizationService->createOrganization('Grandchild', parent: $childOrg);

        // 刷新所有实体以确保关系正确加载
        self::getEntityManager()->refresh($parentOrg);
        self::getEntityManager()->refresh($childOrg);
        self::getEntityManager()->refresh($grandchildOrg);

        $parentId = $parentOrg->getId();
        $this->assertNotNull($parentId, 'Parent organization ID should not be null');
        $childId = $childOrg->getId();
        $this->assertNotNull($childId, 'Child organization ID should not be null');
        $grandchildId = $grandchildOrg->getId();
        $this->assertNotNull($grandchildId, 'Grandchild organization ID should not be null');

        // 验证关系存在
        $this->assertFalse($parentOrg->getChildren()->isEmpty());
        $this->assertFalse($childOrg->getChildren()->isEmpty());

        $this->organizationService->deleteOrganization($parentOrg, force: true);

        // 清除EntityManager缓存以确保从数据库获取最新数据
        self::getEntityManager()->clear();

        // 验证所有组织都被删除
        $this->assertNull($this->organizationRepository->find($parentId), '父组织应被删除');
        $this->assertNull($this->organizationRepository->find($childId), '子组织应被删除');
        $this->assertNull($this->organizationRepository->find($grandchildId), '孙子组织应被删除');
    }

    /**
     * 测试移动组织到新父组织
     */
    #[Test]
    public function testMoveOrganization(): void
    {
        $originalParent = $this->organizationService->createOrganization('Original Parent');
        $newParent = $this->organizationService->createOrganization('New Parent');
        $childOrg = $this->organizationService->createOrganization('Child', parent: $originalParent);

        $this->organizationService->moveOrganization($childOrg, $newParent);

        $this->assertSame($newParent, $childOrg->getParent());

        // 刷新实体以获取最新的集合状态
        self::getEntityManager()->refresh($newParent);
        self::getEntityManager()->refresh($originalParent);

        $this->assertTrue($newParent->getChildren()->contains($childOrg));
        $this->assertFalse($originalParent->getChildren()->contains($childOrg));
    }

    /**
     * 测试不能将组织设置为自己的父组织
     */
    #[Test]
    public function testCannotSetOrganizationAsSelfParent(): void
    {
        $organization = $this->organizationService->createOrganization('Self Reference Test');

        $this->expectException(OrganizationException::class);
        $this->expectExceptionMessage('组织不能设置自己为父组织');

        $this->organizationService->moveOrganization($organization, $organization);
    }

    /**
     * 测试不能创建循环引用
     */
    #[Test]
    public function testCannotCreateCircularReference(): void
    {
        $grandparent = $this->organizationService->createOrganization('Grandparent');
        $parent = $this->organizationService->createOrganization('Parent', parent: $grandparent);
        $child = $this->organizationService->createOrganization('Child', parent: $parent);

        $this->expectException(OrganizationException::class);
        $this->expectExceptionMessage('不能将组织设置为其子组织的父组织');

        // 尝试将祖父组织设为孙子组织的子组织
        $this->organizationService->moveOrganization($grandparent, $child);
    }

    /**
     * 测试获取组织路径
     */
    #[Test]
    public function testGetOrganizationPath(): void
    {
        $root = $this->organizationService->createOrganization('Root Corp');
        $division = $this->organizationService->createOrganization('Tech Division', parent: $root);
        $department = $this->organizationService->createOrganization('Software Dept', parent: $division);
        $team = $this->organizationService->createOrganization('Backend Team', parent: $department);

        $path = $this->organizationService->getOrganizationPath($team);

        $this->assertCount(4, $path);
        $this->assertSame($root, $path[0]);
        $this->assertSame($division, $path[1]);
        $this->assertSame($department, $path[2]);
        $this->assertSame($team, $path[3]);
    }

    /**
     * 测试按管理员查找组织
     */
    #[Test]
    public function testFindOrganizationsByManager(): void
    {
        $manager1 = $this->createNormalUser('manager1@test.com', 'password123');
        $manager2 = $this->createNormalUser('manager2@test.com', 'password123');

        $org1 = $this->organizationService->createOrganization('Org 1', manager: $manager1);
        $org2 = $this->organizationService->createOrganization('Org 2', manager: $manager1);
        $org3 = $this->organizationService->createOrganization('Org 3', manager: $manager2);

        // 无效组织不应被找到
        $invalidOrg = $this->organizationService->createOrganization('Invalid Org', manager: $manager1);
        $this->organizationService->updateOrganization($invalidOrg, valid: false);

        $manager1Orgs = $this->organizationService->findOrganizationsByManager($manager1);
        $manager2Orgs = $this->organizationService->findOrganizationsByManager($manager2);

        $this->assertCount(2, $manager1Orgs);
        $this->assertContains($org1, $manager1Orgs);
        $this->assertContains($org2, $manager1Orgs);
        $this->assertNotContains($invalidOrg, $manager1Orgs);

        $this->assertCount(1, $manager2Orgs);
        $this->assertContains($org3, $manager2Orgs);
    }

    /**
     * 测试搜索组织功能
     */
    #[Test]
    public function testSearchOrganizations(): void
    {
        $this->organizationService->createOrganization('Technology Department');
        $this->organizationService->createOrganization('Technical Support');
        $this->organizationService->createOrganization('Human Resources');
        $this->organizationService->createOrganization('Finance');

        $results = $this->organizationService->searchOrganizations('tech');

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));

        // 验证搜索结果包含关键词
        foreach ($results as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertStringContainsStringIgnoringCase('tech', $org->getName());
        }
    }

    /**
     * 测试查找组织共同祖先
     */
    #[Test]
    public function testFindCommonAncestor(): void
    {
        // 创建组织层级：根 -> 部门1，部门2 -> 团队1（部门1下），团队2（部门2下）
        $root = $this->organizationService->createOrganization('Root Corp');
        $dept1 = $this->organizationService->createOrganization('Department 1', parent: $root);
        $dept2 = $this->organizationService->createOrganization('Department 2', parent: $root);
        $team1 = $this->organizationService->createOrganization('Team 1', parent: $dept1);
        $team2 = $this->organizationService->createOrganization('Team 2', parent: $dept2);

        // 测试同一父级下的组织
        $common1 = $this->organizationService->findCommonAncestor($dept1, $dept2);
        $this->assertSame($root, $common1, '同一父级下的组织共同祖先应该是父级');

        // 测试不同分支下的组织
        $common2 = $this->organizationService->findCommonAncestor($team1, $team2);
        $this->assertSame($root, $common2, '不同分支下的组织共同祖先应该是根组织');

        // 测试同一分支下的组织
        $common3 = $this->organizationService->findCommonAncestor($dept1, $team1);
        $this->assertSame($dept1, $common3, '同一分支下的祖先和后代，共同祖先应该是祖先本身');

        // 测试相同的组织
        $common4 = $this->organizationService->findCommonAncestor($team1, $team1);
        $this->assertSame($team1, $common4, '相同组织的共同祖先应该是自身');

        // 测试没有共同祖先的情况（两个不同的根组织）
        $anotherRoot = $this->organizationService->createOrganization('Another Root');
        $common5 = $this->organizationService->findCommonAncestor($root, $anotherRoot);
        $this->assertNull($common5, '不同根组织之间应该没有共同祖先');
    }

    /**
     * 测试获取组织统计信息
     */
    #[Test]
    public function testGetOrganizationStatistics(): void
    {
        $manager = $this->createNormalUser('manager@stats-test.com', 'password123');
        $root = $this->organizationService->createOrganization('Root Corp', manager: $manager);
        $child1 = $this->organizationService->createOrganization('Child 1', parent: $root);
        $child2 = $this->organizationService->createOrganization('Child 2', parent: $root);
        $grandchild = $this->organizationService->createOrganization('Grandchild', parent: $child1);

        // 刷新根组织以获取最新的children集合
        self::getEntityManager()->refresh($root);

        // 验证关系确实存在
        $this->assertFalse($root->getChildren()->isEmpty(), '根组织应该有子组织');
        $this->assertCount(2, $root->getChildren(), '根组织应该有2个直接子组织');

        $stats = $this->organizationService->getOrganizationStatistics($root);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('id', $stats);
        $this->assertArrayHasKey('name', $stats);
        $this->assertArrayHasKey('level', $stats);
        $this->assertArrayHasKey('directChildrenCount', $stats);
        $this->assertArrayHasKey('totalDescendantsCount', $stats);
        $this->assertArrayHasKey('hasManager', $stats);
        $this->assertArrayHasKey('managerName', $stats);
        $this->assertArrayHasKey('isRoot', $stats);
        $this->assertArrayHasKey('isLeaf', $stats);
        $this->assertArrayHasKey('fullPath', $stats);

        $this->assertSame($root->getId(), $stats['id']);
        $this->assertSame('Root Corp', $stats['name']);
        $this->assertSame(2, $stats['directChildrenCount']);
        $this->assertSame(3, $stats['totalDescendantsCount']); // 2个子组织 + 1个孙子组织
        $this->assertTrue($stats['hasManager']);
        $this->assertSame('manager@stats-test.com', $stats['managerName']);
        $this->assertTrue($stats['isRoot']);
        $this->assertFalse($stats['isLeaf'], '根组织有子组织，所以不应该是叶子节点');
    }
}
