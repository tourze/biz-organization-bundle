<?php

declare(strict_types=1);

namespace BizOrganizationBundle\DataFixtures;

use BizOrganizationBundle\Entity\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OrganizationFixtures extends Fixture implements FixtureGroupInterface
{
    public const ORG_ROOT_REFERENCE = 'org-root';
    public const ORG_TECH_REFERENCE = 'org-tech';
    public const ORG_HR_REFERENCE = 'org-hr';
    public const ORG_FINANCE_REFERENCE = 'org-finance';
    public const ORG_MARKETING_REFERENCE = 'org-marketing';
    public const ORG_FRONTEND_REFERENCE = 'org-frontend';
    public const ORG_BACKEND_REFERENCE = 'org-backend';
    public const ORG_QA_REFERENCE = 'org-qa';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('zh_CN');

        $rootOrg = new Organization();
        $rootOrg->setName('总公司');
        $rootOrg->setDescription('公司总部');
        $rootOrg->setCode('ROOT');
        $rootOrg->setSortNumber(0);
        $rootOrg->setValid(true);

        $manager->persist($rootOrg);
        $this->addReference(self::ORG_ROOT_REFERENCE, $rootOrg);

        $departments = [
            ['name' => '技术部', 'code' => 'TECH', 'description' => '负责技术研发', 'reference' => self::ORG_TECH_REFERENCE],
            ['name' => '人事部', 'code' => 'HR', 'description' => '负责人力资源管理', 'reference' => self::ORG_HR_REFERENCE],
            ['name' => '财务部', 'code' => 'FINANCE', 'description' => '负责财务管理', 'reference' => self::ORG_FINANCE_REFERENCE],
            ['name' => '市场部', 'code' => 'MARKETING', 'description' => '负责市场营销', 'reference' => self::ORG_MARKETING_REFERENCE],
        ];

        foreach ($departments as $index => $deptData) {
            $dept = new Organization();
            $dept->setName($deptData['name']);
            $dept->setCode($deptData['code']);
            $dept->setDescription($deptData['description']);
            $dept->setParent($rootOrg);
            $dept->setSortNumber($index * 10);
            $dept->setValid(true);

            $manager->persist($dept);
            $this->addReference($deptData['reference'], $dept);

            if ('TECH' === $deptData['code']) {
                $techSubDepts = [
                    ['name' => '前端开发组', 'code' => 'FRONTEND', 'reference' => self::ORG_FRONTEND_REFERENCE],
                    ['name' => '后端开发组', 'code' => 'BACKEND', 'reference' => self::ORG_BACKEND_REFERENCE],
                    ['name' => '测试组', 'code' => 'QA', 'reference' => self::ORG_QA_REFERENCE],
                ];

                foreach ($techSubDepts as $subIndex => $subDeptData) {
                    $subDept = new Organization();
                    $subDept->setName($subDeptData['name']);
                    $subDept->setCode($subDeptData['code']);
                    $subDept->setParent($dept);
                    $subDept->setSortNumber($subIndex * 10);
                    $subDept->setValid(true);

                    $manager->persist($subDept);
                    $this->addReference($subDeptData['reference'], $subDept);
                }
            }
        }

        for ($i = 1; $i <= 5; ++$i) {
            $branch = new Organization();
            $branch->setName("分公司{$i}");
            $branch->setCode("BRANCH{$i}");
            $branch->setDescription($faker->text(100));
            $branch->setParent($rootOrg);
            $branch->setPhone($faker->phoneNumber);
            $branch->setAddress($faker->address);
            $branch->setSortNumber(100 + $i * 10);
            $branch->setValid(true);

            $manager->persist($branch);
            $this->addReference("org_branch{$i}", $branch);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['BizOrganizationBundle'];
    }
}
