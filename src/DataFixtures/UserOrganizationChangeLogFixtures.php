<?php

declare(strict_types=1);

namespace BizOrganizationBundle\DataFixtures;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Tourze\UserServiceContracts\UserManagerInterface;
use Tourze\UserServiceContracts\UserServiceConstants;

class UserOrganizationChangeLogFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function __construct(
        private readonly ?UserManagerInterface $userManager = null,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 通过用户服务获取用户，若不可用则跳过
        $adminUser = null;
        $normalUser = null;
        try {
            $adminUser = $this->userManager?->loadUserByIdentifier('admin');
            $normalUser = $this->userManager?->loadUserByIdentifier('user1');
        } catch (\Throwable) {
            // ignore
        }

        $rootOrg = $this->getReference(OrganizationFixtures::ORG_ROOT_REFERENCE, Organization::class);
        $techOrg = $this->getReference(OrganizationFixtures::ORG_TECH_REFERENCE, Organization::class);
        $hrOrg = $this->getReference(OrganizationFixtures::ORG_HR_REFERENCE, Organization::class);

        // 若未获取到用户，则无需创建变更日志
        if (null === $adminUser || null === $normalUser) {
            $manager->flush();

            return;
        }

        $changeLogs = [
            [
                'user' => $adminUser,
                'organization' => $rootOrg,
                'newOrganization' => $techOrg,
                'content' => '管理员从总公司调入技术部',
            ],
            [
                'user' => $adminUser,
                'organization' => $techOrg,
                'newOrganization' => $hrOrg,
                'content' => '管理员从技术部调入人事部',
            ],
            [
                'user' => $normalUser,
                'organization' => $rootOrg,
                'newOrganization' => $techOrg,
                'content' => '普通用户从总公司调入技术部',
            ],
        ];

        foreach ($changeLogs as $index => $logData) {
            $changeLog = new UserOrganizationChangeLog();
            $changeLog->setUser($logData['user']);
            $changeLog->setOrganization($logData['organization']);
            $changeLog->setNewOrganization($logData['newOrganization']);
            $changeLog->setContent($logData['content']);

            $manager->persist($changeLog);
            $this->addReference("change_log_{$index}", $changeLog);
        }

        for ($i = 1; $i <= 5; ++$i) {
            $changeLog = new UserOrganizationChangeLog();
            $changeLog->setUser($normalUser);
            $changeLog->setOrganization($rootOrg);
            $changeLog->setContent($faker->sentence(6));

            $manager->persist($changeLog);
            $this->addReference("random_change_log_{$i}", $changeLog);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'BizOrganizationBundle',
            UserServiceConstants::USER_FIXTURES_NAME,
        ];
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixtures::class,
        ];
    }
}
