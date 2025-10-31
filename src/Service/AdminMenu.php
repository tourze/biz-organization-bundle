<?php

namespace BizOrganizationBundle\Service;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
#[AutoconfigureTag(name: 'easy-admin-menu.provider')]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private ?LinkGeneratorInterface $linkGenerator = null,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $this->linkGenerator) {
            return;
        }

        if (null === $item->getChild('组织管理')) {
            $item->addChild('组织管理');
        }

        $organizationMenu = $item->getChild('组织管理');
        if (null === $organizationMenu) {
            return;
        }

        // 组织架构菜单
        $organizationMenu->addChild('组织架构')
            ->setUri($this->linkGenerator->getCurdListPage(Organization::class))
            ->setAttribute('icon', 'fas fa-sitemap')
        ;

        // 用户组织关联菜单
        $organizationMenu->addChild('用户组织关联')
            ->setUri($this->linkGenerator->getCurdListPage(UserOrganization::class))
            ->setAttribute('icon', 'fas fa-users-cog')
        ;

        // 组织变动记录菜单
        $organizationMenu->addChild('变动记录')
            ->setUri($this->linkGenerator->getCurdListPage(UserOrganizationChangeLog::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }
}
