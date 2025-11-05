<?php

declare(strict_types=1);

namespace BizOrganizationBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\UserServiceContracts\UserManagerInterface;
use Tourze\UserServiceContracts\UserServiceConstants;

/**
 * 测试环境用户数据填充
 *
 * 为 biz-organization-bundle 的测试提供必要的用户实体
 * 用于解决 UserOrganizationFixtures 和 UserOrganizationChangeLogFixtures 的依赖问题
 */
class TestUserFixtures extends Fixture implements FixtureGroupInterface
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const NORMAL_USER_REFERENCE = 'normal-user';

    public function __construct(
        private readonly UserManagerInterface $userManager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 创建管理员用户
        $adminUser = $this->userManager->createUser(
            userIdentifier: 'admin',
            password: 'password',
            roles: ['ROLE_ADMIN'],
        );

        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        // 创建普通用户
        $normalUser = $this->userManager->createUser(
            userIdentifier: 'user1',
            password: 'password',
            roles: ['ROLE_USER'],
        );

        $manager->persist($normalUser);
        $this->addReference(self::NORMAL_USER_REFERENCE, $normalUser);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'BizOrganizationBundle',
            UserServiceConstants::USER_FIXTURES_NAME,
        ];
    }
}
