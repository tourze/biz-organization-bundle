<?php

namespace BizOrganizationBundle\Tests\Fixtures;

use BizOrganizationBundle\Entity\Organization;

class TestOrganization
{
    public static function createRootOrganization(): Organization
    {
        $org = new Organization();
        $org->setName('Test Root Organization')
            ->setDescription('Root organization for testing')
            ->setCode('TEST_ROOT')
            ->setSortNumber(0)
            ->setIsEnabled(true)
        ;

        return $org;
    }

    public static function createChildOrganization(Organization $parent): Organization
    {
        $org = new Organization();
        $org->setName('Test Child Organization')
            ->setDescription('Child organization for testing')
            ->setCode('TEST_CHILD')
            ->setParent($parent)
            ->setSortNumber(10)
            ->setIsEnabled(true)
        ;

        return $org;
    }

    public static function createOrganizationWithManager(): Organization
    {
        $org = new Organization();
        $org->setName('Test Managed Organization')
            ->setDescription('Organization with manager for testing')
            ->setCode('TEST_MANAGED')
            ->setSortNumber(0)
            ->setIsEnabled(true)
            ->setPhone('123-456-7890')
            ->setAddress('Test Address')
        ;

        return $org;
    }
}
