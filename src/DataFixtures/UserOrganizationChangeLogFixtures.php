<?php

declare(strict_types=1);

namespace BizOrganizationBundle\DataFixtures;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\UserServiceContracts\UserServiceConstants;

class UserOrganizationChangeLogFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        // 从数据库查询用户而不是使用 reference（解决跨进程 reference 失效问题）
        /** @var EntityManagerInterface $manager */
        $userRepository = $manager->getRepository(UserInterface::class);
        $adminUser = $userRepository->findOneBy(['userIdentifier' => 'admin']);
        $normalUser = $userRepository->findOneBy(['userIdentifier' => 'user1']);

        // 如果用户不存在则跳过（TestUserFixtures 可能未执行）
        if (null === $adminUser || null === $normalUser) {
            return;
        }

        $rootOrg = $this->getReference(OrganizationFixtures::ORG_ROOT_REFERENCE, Organization::class);
        $techOrg = $this->getReference(OrganizationFixtures::ORG_TECH_REFERENCE, Organization::class);
        $hrOrg = $this->getReference(OrganizationFixtures::ORG_HR_REFERENCE, Organization::class);

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
            TestUserFixtures::class,
            OrganizationFixtures::class,
        ];
    }
}
