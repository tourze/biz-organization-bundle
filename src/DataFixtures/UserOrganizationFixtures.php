<?php

declare(strict_types=1);

namespace BizOrganizationBundle\DataFixtures;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\UserServiceContracts\UserManagerInterface;
use Tourze\UserServiceContracts\UserServiceConstants;

class UserOrganizationFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const USER_ORG_REFERENCE_PREFIX = 'user_org_';

    public function __construct(
        private readonly ?UserManagerInterface $userManager = null,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
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

        // 若未获取到用户，则无需创建关联
        if (null === $adminUser || null === $normalUser) {
            $manager->flush();

            return;
        }

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
