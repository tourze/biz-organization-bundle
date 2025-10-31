# BizOrganizationBundle

[English](README.md) | [中文](README.zh-CN.md)

多级组织管理模块，支持树形结构的组织架构管理。

## 功能特性

- ✅ 多级组织树形结构
- ✅ 组织负责人关联
- ✅ 灵活的组织查询（按层级、名称、编码等）
- ✅ 完整的 CRUD 管理界面（基于 EasyAdmin）
- ✅ 组织统计信息
- ✅ 数据验证和约束
- ✅ 完整的单元测试覆盖

## 核心组件

### 实体类
- `Organization` - 组织实体，支持自引用的树形结构

### 服务类
- `OrganizationService` - 组织业务逻辑服务
- `OrganizationRepository` - 组织数据访问层

### 管理界面
- `OrganizationCrudController` - EasyAdmin 管理界面

## 数据模型

### Organization 实体字段

| 字段 | 类型            | 描述 |
|------|---------------|------|
| id | int           | 主键ID |
| name | string        | 组织名称（必填） |
| description | string        | 组织描述 |
| code | string        | 组织编码（唯一） |
| sortOrder | int           | 排序顺序 |
| isEnabled | bool          | 启用状态 |
| parent | Organization  | 上级组织（自引用） |
| children | Collection    | 下级组织集合 |
| manager | UserInterface | 组织负责人 |
| phone | string        | 联系电话 |
| address | string        | 办公地址 |

## 基础用法

### 创建组织

```php
use BizOrganizationBundle\Service\OrganizationService;

$organizationService = $container->get(OrganizationService::class);

// 创建根组织
$rootOrg = $organizationService->createOrganization(
    '总公司',
    '公司总部',
    'ROOT'
);

// 创建子组织
$techDept = $organizationService->createOrganization(
    '技术部',
    '负责技术研发',
    'TECH',
    $rootOrg // 父组织
);
```

### 查询组织

```php
use BizOrganizationBundle\Repository\OrganizationRepository;

$organizationRepo = $container->get(OrganizationRepository::class);

// 获取所有根组织
$rootOrganizations = $organizationRepo->findRootOrganizations();

// 获取组织树结构
$tree = $organizationRepo->findTreeStructure();

// 按编码查找
$org = $organizationRepo->findByCode('TECH');

// 按层级查找
$level1Orgs = $organizationRepo->findByLevel(1);
```

### 组织关系操作

```php
// 获取组织路径
$path = $organizationService->getOrganizationPath($organization);

// 获取所有下级组织
$subordinates = $organizationService->getAllSubordinateOrganizations($organization);

// 检查祖先关系
$isAncestor = $organizationService->isAncestor($parentOrg, $childOrg);

// 查找共同祖先
$commonAncestor = $organizationService->findCommonAncestor($org1, $org2);
```

## 安装配置

1. 在主项目的 `bundles.php` 中注册 Bundle：

```php
return [
    // ... 其他 bundles
    BizOrganizationBundle\BizOrganizationBundle::class => ['all' => true],
];
```

2. 运行数据库迁移：

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

3. （可选）加载测试数据：

```bash
php bin/console doctrine:fixtures:load --append
```

## 管理界面

通过 EasyAdmin 提供完整的管理界面：

- 组织列表：支持搜索、排序、分页
- 组织详情：显示完整的组织信息和关系
- 组织编辑：可视化的表单编辑
- 权限控制：支持基于角色的访问控制

## 依赖要求

- PHP 8.1+
- Symfony 7.3+
- Doctrine ORM 3.0+
- EasyAdmin Bundle 4+
- tourze/biz-user-bundle 0.0.*