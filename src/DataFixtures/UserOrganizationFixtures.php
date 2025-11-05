<?php

declare(strict_types=1);

namespace BizOrganizationBundle\DataFixtures;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\UserServiceContracts\UserServiceConstants;

class UserOrganizationFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const USER_ORG_REFERENCE_PREFIX = 'user_org_';

    public function load(ObjectManager $manager): void
    {
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

        $userOrganizations = [
            [
                'user' => $adminUser,
                'organization' => $rootOrg,
            ],
            [
                'user' => $normalUser,
                'organization' => $techOrg,
            ],
        ];

        foreach ($userOrganizations as $index => $data) {
            $userOrg = new UserOrganization();
            $userOrg->setUser($data['user']);
            $userOrg->setOrganization($data['organization']);

            $manager->persist($userOrg);
            $this->addReference(self::USER_ORG_REFERENCE_PREFIX . ($index + 1), $userOrg);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TestUserFixtures::class,
            OrganizationFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return [
            'BizOrganizationBundle',
            UserServiceConstants::USER_FIXTURES_NAME,
        ];
    }
}
