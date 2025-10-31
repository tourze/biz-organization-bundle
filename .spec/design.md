# BizOrganization Bundle 技术设计文档

## 概述

本文档详细描述了 BizOrganization Bundle 的技术架构设计，基于已完成的需求规格说明书，为后续实现提供详细的技术指导。该包与 Role-Based Access Control Bundle 协同工作，实现基于组织架构的数据隔离。

## 架构概览

### 核心设计原则

1. **层级组织管理**：支持无限层级的组织结构
2. **数据隔离基础**：为数据隔离提供组织架构基础
3. **灵活的组织关系**：支持用户多组织关联和主要组织标识
4. **易于集成**：提供 EasyAdmin 无缝集成，降低使用门槛

### 技术栈

- **PHP 8.1+**
- **Symfony 6.4+**
- **Doctrine ORM 2.15+**
- **PHPUnit 9.0+**
- **PHPStan Level 8**

## 核心实体设计

### 1. Organization 实体（已存在）

```php
#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'biz_organization')]
class Organization
{
    use TimestampableAware;
    use SnowflakeKeyAware;
    use SortableTrait;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name = '';

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $valid = true;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(value: ['sortNumber' => 'ASC', 'name' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(name: 'manager_id', type: Types::STRING, length: 255, nullable: true)]
    private ?string $managerId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\OneToMany(targetEntity: UserOrganization::class, mappedBy: 'organization')]
    private Collection $userOrganizations;
}
```

### 2. UserOrganization 实体（需要创建）

```php
#[ORM\Entity(repositoryClass: UserOrganizationRepository::class)]
#[ORM\Table(name: 'biz_user_organization')]
#[ORM\UniqueConstraint(name: 'user_organization_unique', columns: ['user_id', 'organization_id'])]
class UserOrganization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $userId; // 用户标识符

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'userOrganizations')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false)]
    private Organization $organization;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPrimary = false;

    #[ORM\Column(name: 'joined_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(name: 'left_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $leftAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $role = null; // 在组织中的角色

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }
}
```

## 数据库设计

### 核心表结构

```sql
-- 组织表（已存在）
CREATE TABLE biz_organization (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    code VARCHAR(100) NULL,
    valid BOOLEAN DEFAULT TRUE,
    parent_id BIGINT NULL,
    manager_id VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    sort_number INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX parent_idx (parent_id),
    INDEX code_idx (code),
    INDEX manager_idx (manager_id),
    INDEX valid_idx (valid),
    FOREIGN KEY (parent_id) REFERENCES biz_organization(id) ON DELETE SET NULL
);

-- 用户组织关联表（需要创建）
CREATE TABLE biz_user_organization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    organization_id BIGINT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    joined_at DATETIME NOT NULL,
    left_at DATETIME NULL,
    role VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY user_organization_unique (user_id, organization_id),
    INDEX user_organization_user_idx (user_id),
    INDEX user_organization_organization_idx (organization_id),
    INDEX user_organization_primary_idx (is_primary),
    INDEX user_organization_joined_idx (joined_at),
    FOREIGN KEY (organization_id) REFERENCES biz_organization(id) ON DELETE CASCADE
);
```

## 服务层设计

### OrganizationManagerInterface 接口

```php
interface OrganizationManagerInterface
{
    // 组织管理
    public function createOrganization(array $data): Organization;
    public function updateOrganization(Organization $organization, array $data): Organization;
    public function deleteOrganization(Organization $organization, bool $force = false): void;
    public function moveOrganization(Organization $organization, ?Organization $newParent): void;
    
    // 层级管理
    public function getOrganizationTree(): array;
    public function getOrganizationPath(Organization $organization): array;
    public function getSubordinateOrganizations(Organization $organization, bool $includeDisabled = false): array;
    public function getAllSubordinateOrganizations(Organization $organization, bool $includeDisabled = false): array;
    
    // 用户组织关联
    public function assignUserToOrganization(UserInterface $user, Organization $organization, array $options = []): UserOrganization;
    public function removeUserFromOrganization(UserInterface $user, Organization $organization): bool;
    public function setUserPrimaryOrganization(UserInterface $user, Organization $organization): void;
    public function getUserOrganizations(UserInterface $user): array;
    public function getUserPrimaryOrganization(UserInterface $user): ?Organization;
    
    // 数据隔离核心方法
    public function getUserAccessibleOrganizationIds(UserInterface $user): array;
    public function canAccessOrganization(UserInterface $user, int $organizationId): bool;
    public function getOrganizationHierarchy(int $organizationId): array;
    public function createIsolatedQueryBuilder(string $entityClass, UserInterface $user): QueryBuilder;
    
    // 查询和搜索
    public function searchOrganizations(string $keyword): array;
    public function findOrganizationsByManager(UserInterface $manager): array;
    public function getOrganizationStatistics(Organization $organization): array;
    
    // 验证方法
    public function isAncestor(Organization $ancestor, Organization $descendant): bool;
    public function findCommonAncestor(Organization $org1, Organization $org2): ?Organization;
}
```

### 数据隔离服务接口

```php
interface DataIsolationServiceInterface
{
    /**
     * 检查实体是否支持组织隔离
     */
    public function supportsOrganizationIsolation(string $entityClass): bool;
    
    /**
     * 获取用户可访问的组织ID列表
     */
    public function getUserAccessibleOrganizationIds(UserInterface $user): array;
    
    /**
     * 检查用户是否有权限访问指定组织
     */
    public function canAccessOrganization(UserInterface $user, int $organizationId): bool;
    
    /**
     * 创建带组织过滤的查询构建器
     */
    public function createIsolatedQueryBuilder(string $entityClass, UserInterface $user): QueryBuilder;
    
    /**
     * 在查询构建器上应用组织过滤
     */
    public function applyOrganizationFilter(QueryBuilder $queryBuilder, UserInterface $user, string $organizationFieldAlias): void;
    
    /**
     * 获取组织层级结构
     */
    public function getOrganizationHierarchy(int $organizationId): array;
}
```

## 关键实现细节

### 1. 层级组织管理

```php
public function getAllSubordinateOrganizations(Organization $organization, bool $includeDisabled = false): array
{
    $allSubordinates = [];
    $directSubordinates = $this->getSubordinateOrganizations($organization, $includeDisabled);

    foreach ($directSubordinates as $subordinate) {
        $allSubordinates[] = $subordinate;
        $allSubordinates = array_merge(
            $allSubordinates,
            $this->getAllSubordinateOrganizations($subordinate, $includeDisabled)
        );
    }

    return $allSubordinates;
}
```

### 2. 用户组织关联管理

```php
public function assignUserToOrganization(UserInterface $user, Organization $organization, array $options = []): UserOrganization
{
    $userId = $user->getUserIdentifier();
    
    // 检查是否已存在关联
    $existing = $this->userOrganizationRepository->findOneBy([
        'userId' => $userId,
        'organization' => $organization,
        'leftAt' => null
    ]);
    
    if ($existing) {
        return $existing;
    }
    
    // 创建新关联
    $userOrganization = new UserOrganization();
    $userOrganization->setUser($user);
    $userOrganization->setOrganization($organization);
    $userOrganization->setPrimary($options['is_primary'] ?? false);
    
    // 如果设置为主要组织，清除其他主要组织标记
    if ($userOrganization->isPrimary()) {
        $this->clearOtherPrimaryOrganizations($userId);
    }
    
    $this->entityManager->persist($userOrganization);
    $this->entityManager->flush();
    
    return $userOrganization;
}
```

### 3. 数据隔离核心实现

```php
public function getUserAccessibleOrganizationIds(UserInterface $user): array
{
    $userOrganizations = $this->getUserOrganizations($user);
    $accessibleIds = [];
    
    foreach ($userOrganizations as $userOrganization) {
        $organization = $userOrganization->getOrganization();
        $accessibleIds[] = $organization->getId();
        
        // 获取所有子组织ID
        $subordinateIds = $this->getAllSubordinateOrganizationIds($organization);
        $accessibleIds = array_merge($accessibleIds, $subordinateIds);
    }
    
    return array_unique($accessibleIds);
}

public function createIsolatedQueryBuilder(string $entityClass, UserInterface $user): QueryBuilder
{
    if (!$this->supportsOrganizationIsolation($entityClass)) {
        throw new \InvalidArgumentException("Entity {$entityClass} does not support organization isolation");
    }
    
    $accessibleIds = $this->getUserAccessibleOrganizationIds($user);
    
    if (empty($accessibleIds)) {
        // 用户没有任何组织访问权限，返回空的查询
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($entityClass, 'e')
            ->where('1 = 0'); // 永远不会返回结果
    }
    
    return $this->entityManager->createQueryBuilder()
        ->select('e')
        ->from($entityClass, 'e')
        ->where('e.organization IN (:accessibleIds)')
        ->setParameter('accessibleIds', $accessibleIds);
}
```

## EasyAdmin 集成设计

### 1. CRUD 扩展

```php
class OrganizationCrudController extends AbstractCrudController
{
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextareaField::new('description'),
            TextField::new('code'),
            AssociationField::new('parent')->setRequired(false),
            BooleanField::new('valid'),
            TextField::new('managerId'),
            TextField::new('phone'),
            TextField::new('address'),
            IntegerField::new('sortNumber'),
        ];
    }
    
    public function configureActions(Actions $actions): Actions
    {
        // 添加自定义操作
        return $actions
            ->add(Crud::PAGE_INDEX, Action::new('viewTree', '查看组织树', 'fa fa-sitemap'))
            ->add(Crud::PAGE_INDEX, Action::new('exportStructure', '导出结构', 'fa fa-download'));
    }
}
```

### 2. 数据过滤扩展

```php
class DataIsolationCrudExtension implements CrudControllerExtensionInterface
{
    public function configureQuery(CrudControllerInterface $controller, SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): void
    {
        $user = $this->security->getUser();
        if (!$user || !$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        // 检查实体是否支持组织隔离
        if ($this->dataIsolationService->supportsOrganizationIsolation($entityDto->getFqcn())) {
            $queryBuilder = $searchDto->getQueryBuilder();
            
            // 应用组织过滤
            $this->dataIsolationService->applyOrganizationFilter(
                $queryBuilder,
                $user,
                'entity.organization' // 假设组织字段的关联路径是 entity.organization
            );
        }
    }
}
```

## 事件系统设计

### 组织事件定义

```php
class OrganizationCreatedEvent
{
    public const NAME = 'organization.created';
    
    public function __construct(
        private readonly Organization $organization,
        private readonly ?UserInterface $createdBy = null,
        private readonly \DateTimeImmutable $occurredAt
    ) {}
}

class OrganizationUpdatedEvent
{
    public const NAME = 'organization.updated';
    
    public function __construct(
        private readonly Organization $organization,
        private readonly array $changes,
        private readonly ?UserInterface $updatedBy = null,
        private readonly \DateTimeImmutable $occurredAt
    ) {}
}

class UserAssignedToOrganizationEvent
{
    public const NAME = 'user.assigned_to_organization';
    
    public function __construct(
        private readonly UserInterface $user,
        private readonly Organization $organization,
        private readonly UserOrganization $userOrganization,
        private readonly ?UserInterface $assignedBy = null,
        private readonly \DateTimeImmutable $occurredAt
    ) {}
}
```

### 事件监听器

```php
class OrganizationEventListener
{
    #[AsEventListener(event: OrganizationCreatedEvent::NAME)]
    public function onOrganizationCreated(OrganizationCreatedEvent $event): void
    {
        // 记录审计日志
        $this->auditLogger->log('organization.created', [
            'organization_id' => $event->getOrganization()->getId(),
            'organization_name' => $event->getOrganization()->getName(),
            'created_by' => $event->getCreatedBy()?->getUserIdentifier(),
            'occurred_at' => $event->getOccurredAt()->format('c')
        ]);
        
        // 清除相关缓存
        $this->cacheManager->clearOrganizationCache($event->getOrganization()->getId());
    }
    
    #[AsEventListener(event: UserAssignedToOrganizationEvent::NAME)]
    public function onUserAssignedToOrganization(UserAssignedToOrganizationEvent $event): void
    {
        // 更新用户权限缓存
        $this->permissionCache->invalidateUserCache($event->getUser()->getUserIdentifier());
        
        // 发送通知
        $this->notificationService->sendOrganizationAssignmentNotification(
            $event->getUser(),
            $event->getOrganization(),
            $event->getUserOrganization()
        );
    }
}
```

## 性能优化策略

### 1. 层级缓存

```php
class OrganizationHierarchyCache
{
    public function getSubordinateIds(int $organizationId): array
    {
        $cacheKey = "organization_subordinate_ids:{$organizationId}";
        
        return $this->cache->get($cacheKey, function () use ($organizationId) {
            $organization = $this->organizationRepository->find($organizationId);
            if (!$organization) {
                return [];
            }
            
            $subordinates = $this->organizationService->getAllSubordinateOrganizations($organization);
            return array_map(fn($org) => $org->getId(), $subordinates);
        });
    }
    
    public function invalidateHierarchy(int $organizationId): void
    {
        $this->cache->delete("organization_subordinate_ids:{$organizationId}");
        
        // 递归清除父级缓存
        $organization = $this->organizationRepository->find($organizationId);
        if ($organization && $organization->getParent()) {
            $this->invalidateHierarchy($organization->getParent()->getId());
        }
    }
}
```

### 2. 查询优化

```php
class OrganizationRepository extends ServiceEntityRepository
{
    /**
     * 优化的层级查询，避免N+1问题
     */
    public function findTreeStructure(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.children', 'c')
            ->leftJoin('o.parent', 'p')
            ->addSelect('c', 'p')
            ->orderBy('o.sortNumber', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * 批量获取用户组织关联
     */
    public function findUserOrganizationsBatch(array $userIds): array
    {
        return $this->createQueryBuilder('uo')
            ->join('uo.organization', 'o')
            ->where('uo.userId IN (:userIds)')
            ->andWhere('uo.leftAt IS NULL')
            ->setParameter('userIds', $userIds)
            ->getQuery()
            ->getResult();
    }
}
```

### 3. 索引优化策略

```sql
-- 组织表索引
CREATE INDEX biz_organization_parent_idx ON biz_organization(parent_id);
CREATE INDEX biz_organization_code_idx ON biz_organization(code);
CREATE INDEX biz_organization_manager_idx ON biz_organization(manager_id);
CREATE INDEX biz_organization_valid_idx ON biz_organization(valid);
CREATE INDEX biz_organization_sort_idx ON biz_organization(sort_number);

-- 用户组织关联表索引
CREATE INDEX biz_user_organization_user_idx ON biz_user_organization(user_id);
CREATE INDEX biz_user_organization_org_idx ON biz_user_organization(organization_id);
CREATE INDEX biz_user_organization_primary_idx ON biz_user_organization(is_primary);
CREATE INDEX biz_user_organization_joined_idx ON biz_user_organization(joined_at);
CREATE INDEX biz_user_organization_composite_idx ON biz_user_organization(user_id, organization_id, is_primary);
```

## 测试策略

### 单元测试

```php
class OrganizationManagerTest extends TestCase
{
    public function testGetUserAccessibleOrganizationIds(): void
    {
        // 测试获取用户可访问的组织ID列表
        $user = $this->createTestUser('test-user');
        $org1 = $this->createTestOrganization('组织1');
        $org2 = $this->createTestOrganization('组织2', $org1); // org1的子组织
        
        // 分配用户到org1
        $this->organizationManager->assignUserToOrganization($user, $org1);
        
        $accessibleIds = $this->organizationManager->getUserAccessibleOrganizationIds($user);
        
        $this->assertContains($org1->getId(), $accessibleIds);
        $this->assertContains($org2->getId(), $accessibleIds); // 应该包含子组织
    }
    
    public function testCanAccessOrganization(): void
    {
        // 测试组织访问权限检查
        $user = $this->createTestUser('test-user');
        $org1 = $this->createTestOrganization('组织1');
        $org2 = $this->createTestOrganization('组织2');
        
        $this->organizationManager->assignUserToOrganization($user, $org1);
        
        $this->assertTrue($this->organizationManager->canAccessOrganization($user, $org1->getId()));
        $this->assertFalse($this->organizationManager->canAccessOrganization($user, $org2->getId()));
    }
    
    public function testCreateIsolatedQueryBuilder(): void
    {
        // 测试创建隔离查询构建器
        $user = $this->createTestUser('test-user');
        $org = $this->createTestOrganization('组织1');
        
        $this->organizationManager->assignUserToOrganization($user, $org);
        
        $qb = $this->organizationManager->createIsolatedQueryBuilder(TestEntity::class, $user);
        $dql = $qb->getDQL();
        
        $this->assertStringContainsString('e.organization IN', $dql);
    }
}
```

### 集成测试

```php
class DataIsolationIntegrationTest extends KernelTestCase
{
    public function testDataIsolationInEasyAdmin(): void
    {
        // 测试EasyAdmin中的数据隔离
        $client = static::createClient();
        
        // 模拟用户登录
        $user = $this->createTestUser('test-user');
        $client->loginUser($user);
        
        // 分配用户到特定组织
        $org = $this->createTestOrganization('测试组织');
        $this->organizationManager->assignUserToOrganization($user, $org);
        
        // 创建测试数据
        $this->createTestDataForOrganization($org);
        $this->createTestDataForOtherOrganization();
        
        // 访问EasyAdmin列表页面
        $crawler = $client->request('GET', '/admin/test-entity');
        
        // 验证只能看到自己组织的数据
        $this->assertSelectorCount('.data-grid-row', 1); // 只应该看到1条数据
    }
}
```

## 部署和配置

### 服务配置

```yaml
# config/services.yaml
services:
    BizOrganizationBundle\Service\OrganizationManager:
        autowire: true
        autoconfigure: true
        alias: 'biz_organization.manager'

    BizOrganizationBundle\Service\DataIsolationService:
        autowire: true
        autoconfigure: true
        alias: 'biz_organization.data_isolation'

    BizOrganizationBundle\EventListener\OrganizationEventListener:
        autowire: true
        autoconfigure: true
        tags: ['kernel.event_listener']

    # 注册EasyAdmin扩展
    BizOrganizationBundle\Admin\Extension\DataIsolationCrudExtension:
        autowire: true
        autoconfigure: true
        tags: ['easy_admin.crud_extension']
```

### Bundle 配置

```yaml
# config/packages/biz_organization.yaml
biz_organization:
    # 层级深度限制
    max_hierarchy_level: 10
    
    # 缓存配置
    cache:
        enabled: true
        ttl: 3600 # 1小时
    
    # 数据隔离配置
    data_isolation:
        enabled: true
        default_strategy: 'hierarchy' # hierarchy, direct_only
        
    # 审计配置
    audit:
        enabled: true
        log_organization_changes: true
        log_user_assignments: true
```

## 扩展点设计

### 1. 自定义组织策略

```php
interface OrganizationAccessStrategyInterface
{
    public function getUserAccessibleOrganizations(UserInterface $user): array;
    public function canAccessOrganization(UserInterface $user, Organization $organization): bool;
}

class CustomAccessStrategy implements OrganizationAccessStrategyInterface
{
    public function getUserAccessibleOrganizations(UserInterface $user): array
    {
        // 实现自定义访问策略
        return [];
    }
}
```

### 2. 自定义审计处理器

```php
interface OrganizationAuditHandlerInterface
{
    public function handleOrganizationChange(Organization $organization, array $changes): void;
    public function handleUserAssignment(UserInterface $user, Organization $organization): void;
}
```

## 总结

本文档详细设计了 BizOrganization Bundle 的技术架构，包括：

1. **实体设计**：Organization 和 UserOrganization 两个核心实体
2. **数据库设计**：优化的表结构和索引策略
3. **服务层设计**：完整的 OrganizationManagerInterface 接口
4. **数据隔离服务**：专门的数据隔离实现
5. **EasyAdmin 集成**：无缝的管理界面集成
6. **事件系统**：支持异步处理和审计
7. **性能优化**：层级缓存、查询优化、索引策略
8. **测试策略**：单元测试、集成测试
9. **扩展点**：支持自定义功能扩展

该设计与 Role-Based Access Control Bundle 深度集成，为基于角色的数据隔离功能提供了完整的组织架构基础。所有设计都基于已确认的需求规格，确保了可实施性和扩展性。