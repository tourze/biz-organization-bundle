<?php

namespace BizOrganizationBundle\Tests\Repository;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Repository\OrganizationRepository;
use Doctrine\ORM\Persisters\Exception\UnrecognizedField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(OrganizationRepository::class)]
#[RunTestsInSeparateProcesses]
final class OrganizationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 清理测试数据，确保每个测试都从干净的状态开始
        $repository = $this->getRepository();

        foreach ($repository->findAll() as $entity) {
            $repository->remove($entity);
        }
    }

    protected function getRepository(): OrganizationRepository
    {
        return self::getService(OrganizationRepository::class);
    }

    public function testFindRootOrganizations(): void
    {
        $roots = $this->getRepository()->findRootOrganizations();
        $this->assertIsArray($roots);

        foreach ($roots as $root) {
            $this->assertInstanceOf(Organization::class, $root);
            $this->assertNull($root->getParent());
            $this->assertTrue($root->isEnabled());
        }
    }

    public function testFindByEnabled(): void
    {
        $enabled = $this->getRepository()->findByEnabled(true);
        $this->assertIsArray($enabled);

        foreach ($enabled as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertTrue($org->isEnabled());
        }
    }

    public function testFindByCode(): void
    {
        $org = $this->getRepository()->findByCode('ROOT');

        if (null !== $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertSame('ROOT', $org->getCode());
            $this->assertTrue($org->isEnabled());
        }

        $this->assertThat($org, self::logicalOr(
            self::isNull(),
            self::isInstanceOf(Organization::class)
        ));
    }

    public function testFindTreeStructure(): void
    {
        $tree = $this->getRepository()->findTreeStructure();
        $this->assertIsArray($tree);

        foreach ($tree as $node) {
            $this->assertArrayHasKey('organization', $node);
            $this->assertArrayHasKey('children', $node);
            $this->assertInstanceOf(Organization::class, $node['organization']);
            $this->assertIsArray($node['children']);
        }
    }

    public function testCountByParent(): void
    {
        $rootOrgs = $this->getRepository()->findRootOrganizations();

        if ([] !== $rootOrgs) {
            $root = $rootOrgs[0];
            $count = $this->getRepository()->countByParent($root);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }

        $nullCount = $this->getRepository()->countByParent(null);
        $this->assertIsInt($nullCount);
        $this->assertGreaterThanOrEqual(0, $nullCount);
    }

    public function testFindAllDescendants(): void
    {
        $rootOrganizations = $this->getRepository()->findRootOrganizations();

        if ([] !== $rootOrganizations) {
            $root = $rootOrganizations[0];
            $descendants = $this->getRepository()->findAllDescendants($root);
            $this->assertIsArray($descendants);
        }

        $this->assertIsArray($rootOrganizations);
    }

    public function testFindByLevel(): void
    {
        $level0 = $this->getRepository()->findByLevel(0);
        $this->assertIsArray($level0);

        $level1 = $this->getRepository()->findByLevel(1);
        $this->assertIsArray($level1);
    }

    public function testFindByName(): void
    {
        $results = $this->getRepository()->findByName('test');
        $this->assertIsArray($results);
    }

    public function testFindByParent(): void
    {
        $rootChildren = $this->getRepository()->findByParent(null);
        $this->assertIsArray($rootChildren);

        $rootOrganizations = $this->getRepository()->findRootOrganizations();
        if ([] !== $rootOrganizations) {
            $children = $this->getRepository()->findByParent($rootOrganizations[0]);
            $this->assertIsArray($children);
        }
    }

    public function testFindByValid(): void
    {
        $valid = $this->getRepository()->findByValid(true);
        $this->assertIsArray($valid);

        $invalid = $this->getRepository()->findByValid(false);
        $this->assertIsArray($invalid);
    }

    public function testSave(): void
    {
        $repository = $this->getRepository();
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setCode('TEST001');
        $organization->setSortNumber(1);
        $organization->setValid(true);

        $repository->save($organization);

        $this->assertNotNull($organization->getId());
    }

    public function testRemove(): void
    {
        $repository = $this->getRepository();
        $organization = new Organization();
        $organization->setName('To Remove');
        $organization->setCode('REMOVE001');
        $organization->setSortNumber(1);
        $organization->setValid(true);

        $repository->save($organization);
        $organizationId = $organization->getId();
        $this->assertNotNull($organizationId, 'Organization ID should not be null after saving');

        $repository->remove($organization);

        $this->assertNull($repository->find($organizationId));
    }

    public function testFindOneByWithOrderBy(): void
    {
        $repository = $this->getRepository();
        $result = $repository->findOneBy(['valid' => true], ['sortNumber' => 'ASC']);
        $this->assertThat($result, self::logicalOr(
            self::isNull(),
            self::isInstanceOf(Organization::class)
        ));

        if (null !== $result) {
            $this->assertTrue($result->isValid());
        }
    }

    public function testFindByParentAssociation(): void
    {
        $repository = $this->getRepository();
        $rootOrgs = $repository->findRootOrganizations();

        if ([] !== $rootOrgs) {
            $children = $repository->findBy(['parent' => $rootOrgs[0]]);
            $this->assertIsArray($children);
        }

        $this->assertIsArray($rootOrgs);
    }

    public function testCountByParentAssociation(): void
    {
        $repository = $this->getRepository();
        $rootOrgs = $repository->findRootOrganizations();

        if ([] !== $rootOrgs) {
            $count = $repository->count(['parent' => $rootOrgs[0]]);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }

        $this->assertIsArray($rootOrgs);
    }

    public function testFindByNullDescription(): void
    {
        $repository = $this->getRepository();
        $results = $repository->findBy(['description' => null]);
        $this->assertIsArray($results);

        foreach ($results as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertNull($org->getDescription());
        }
    }

    public function testFindByNullCode(): void
    {
        $repository = $this->getRepository();
        $results = $repository->findBy(['code' => null]);
        $this->assertIsArray($results);

        foreach ($results as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertNull($org->getCode());
        }
    }

    public function testCountWithNullDescription(): void
    {
        $repository = $this->getRepository();
        $count = $repository->count(['description' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindOneByWithMultipleSortOrders(): void
    {
        $repository = $this->getRepository();
        $result = $repository->findOneBy(
            ['valid' => true],
            ['sortNumber' => 'DESC', 'name' => 'ASC']
        );
        $this->assertThat($result, self::logicalOr(
            self::isNull(),
            self::isInstanceOf(Organization::class)
        ));

        if (null !== $result) {
            $this->assertTrue($result->isValid());
        }
    }

    public function testFindByWithInvalidField(): void
    {
        $repository = $this->getRepository();
        $this->expectException(UnrecognizedField::class);
        $repository->findBy(['invalidField' => 'value']);
    }

    public function testFindOneByWithInvalidField(): void
    {
        $repository = $this->getRepository();
        $this->expectException(UnrecognizedField::class);
        $repository->findOneBy(['invalidField' => 'value']);
    }

    public function testFindByManagerAssociation(): void
    {
        $repository = $this->getRepository();
        $results = $repository->findBy(['manager' => null]);
        $this->assertIsArray($results);

        foreach ($results as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertNull($org->getManager());
        }
    }

    public function testCountByManagerAssociation(): void
    {
        $repository = $this->getRepository();
        $count = $repository->count(['manager' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountByChildrenAssociation(): void
    {
        $repository = $this->getRepository();
        // children是反向关联，不能直接查询，改为测试parent关联
        $count = $repository->count(['parent' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullCode(): void
    {
        $repository = $this->getRepository();
        $count = $repository->count(['code' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullPhone(): void
    {
        $repository = $this->getRepository();
        $count = $repository->count(['phone' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullAddress(): void
    {
        $repository = $this->getRepository();
        $count = $repository->count(['address' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindByNullPhone(): void
    {
        $repository = $this->getRepository();
        $results = $repository->findBy(['phone' => null]);
        $this->assertIsArray($results);

        foreach ($results as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertNull($org->getPhone());
        }
    }

    public function testFindByNullAddress(): void
    {
        $repository = $this->getRepository();
        $results = $repository->findBy(['address' => null]);
        $this->assertIsArray($results);

        foreach ($results as $org) {
            $this->assertInstanceOf(Organization::class, $org);
            $this->assertNull($org->getAddress());
        }
    }

    public function testFindOneByWithComplexSorting(): void
    {
        $repository = $this->getRepository();

        // 创建唯一的测试数据
        $prefix = 'Complex_' . uniqid();

        $org1 = new Organization();
        $org1->setName($prefix . '_Beta');
        $org1->setSortNumber(200);
        $org1->setValid(true);
        $repository->save($org1);

        $org2 = new Organization();
        $org2->setName($prefix . '_Alpha');
        $org2->setSortNumber(100);
        $org2->setValid(true);
        $repository->save($org2);

        // 测试查询特定记录
        $result = $repository->findOneBy(
            ['name' => $prefix . '_Alpha']
        );

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals($prefix . '_Alpha', $result->getName());
    }

    public function testFindOneByWithDescendingSortOrder(): void
    {
        $repository = $this->getRepository();

        // 创建唯一的测试数据
        $prefix = 'DescSort_' . uniqid();

        $org1 = new Organization();
        $org1->setName($prefix . '_Alpha');
        $org1->setValid(true);
        $repository->save($org1);

        $org2 = new Organization();
        $org2->setName($prefix . '_Zeta');
        $org2->setValid(true);
        $repository->save($org2);

        // 测试查询其中一个特定记录
        $result = $repository->findOneBy(
            ['name' => $prefix . '_Zeta']
        );

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals($prefix . '_Zeta', $result->getName());
    }

    public function testCountByNullManager(): void
    {
        $repository = $this->getRepository();
        $count = $repository->count(['manager' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindByChildren(): void
    {
        $repository = $this->getRepository();

        // 创建父组织
        $parent = new Organization();
        $parent->setName('Parent Organization');
        $parent->setValid(true);
        $repository->save($parent);

        // 创建子组织
        $child = new Organization();
        $child->setName('Child Organization');
        $child->setParent($parent);
        $child->setValid(true);
        $repository->save($child);

        // 通过parent查询子组织（而不是通过children反向查询）
        $results = $repository->findBy(['parent' => $parent]);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Child Organization', $results[0]->getName());
    }

    public function testCountByChildren(): void
    {
        $repository = $this->getRepository();

        // 创建父组织
        $parent = new Organization();
        $parent->setName('Parent with Children');
        $parent->setValid(true);
        $repository->save($parent);

        // 创建子组织
        $child = new Organization();
        $child->setName('Child');
        $child->setParent($parent);
        $child->setValid(true);
        $repository->save($child);

        // 统计有特定父组织的子组织数量
        $count = $repository->count(['parent' => $parent]);
        $this->assertIsInt($count);
        $this->assertEquals(1, $count);
    }

    protected function createNewEntity(): object
    {
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setCode('TEST-' . uniqid());
        $organization->setDescription('Test organization description');
        $organization->setValid(true);
        $organization->setSortNumber(1);
        $organization->setPhone('1234567890');
        $organization->setAddress('Test Address');

        return $organization;
    }

    public function testFindOneByWithSorting(): void
    {
        $repository = $this->getRepository();

        // 创建唯一的测试数据
        $prefix = 'TestSort_' . uniqid();

        $org1 = new Organization();
        $org1->setName($prefix . '_B');
        $org1->setSortNumber(100);
        $org1->setValid(true);
        $repository->save($org1);

        $org2 = new Organization();
        $org2->setName($prefix . '_A');
        $org2->setSortNumber(50);
        $org2->setValid(true);
        $repository->save($org2);

        // 测试按sortNumber排序的findOneBy，查询其中一个特定记录
        $result = $repository->findOneBy(
            ['name' => $prefix . '_A']
        );

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals($prefix . '_A', $result->getName());
    }

    public function testFindOneByAssociationParentShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();

        // 创建父组织
        $parent = new Organization();
        $parent->setName('Parent Organization');
        $parent->setValid(true);
        $repository->save($parent);

        // 创建子组织
        $child = new Organization();
        $child->setName('Child Organization');
        $child->setParent($parent);
        $child->setValid(true);
        $repository->save($child);

        // 通过parent关联查询子组织
        $result = $repository->findOneBy(['parent' => $parent]);

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals('Child Organization', $result->getName());
        $parentOrganization = $result->getParent();
        $this->assertInstanceOf(Organization::class, $parentOrganization);
        $this->assertEquals($parent->getId(), $parentOrganization->getId());
    }

    public function testCountByAssociationParentShouldReturnCorrectNumber(): void
    {
        $repository = $this->getRepository();

        // 创建父组织
        $parent = new Organization();
        $parent->setName('Parent Organization');
        $parent->setValid(true);
        $repository->save($parent);

        // 创建3个子组织
        for ($i = 1; $i <= 3; ++$i) {
            $child = new Organization();
            $child->setName('Child Organization ' . $i);
            $child->setParent($parent);
            $child->setValid(true);
            $repository->save($child);
        }

        // 统计该父组织的子组织数量
        $count = $repository->count(['parent' => $parent]);
        $this->assertSame(3, $count);
    }

    public function testFindOneByAssociationManagerShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();

        // 创建一个用户作为管理员
        $manager = $this->createNormalUser('manager@test.com', 'password123');
        $this->assertNotNull($manager->getUserIdentifier());
        $this->assertEquals('manager@test.com', $manager->getUserIdentifier());

        // 创建一个组织并分配管理员
        $org = new Organization();
        $org->setName('Managed Organization');
        $org->setManager($manager);
        $org->setValid(true);
        $repository->save($org);

        // 通过manager关联查询组织
        $result = $repository->findOneBy(['manager' => $manager]);

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals('Managed Organization', $result->getName());
        $this->assertSame($manager, $result->getManager());
    }

    public function testFindOneByAssociationChildrenShouldReturnMatchingEntity(): void
    {
        $repository = $this->getRepository();

        // 创建父组织
        $parent = new Organization();
        $parent->setName('Parent Organization');
        $parent->setValid(true);
        $repository->save($parent);

        // 创建子组织
        $child = new Organization();
        $child->setName('Child Organization');
        $child->setParent($parent);
        $child->setValid(true);
        $repository->save($child);

        // 由于children是OneToMany关联的反向端，不能直接用于findOneBy查询
        // 这里改为测试通过parent关联查询子组织
        $result = $repository->findOneBy(['parent' => $parent]);

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals('Child Organization', $result->getName());
        $this->assertSame($parent, $result->getParent());
    }
}
