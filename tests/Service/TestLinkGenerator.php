<?php

declare(strict_types=1);

namespace BizOrganizationBundle\Tests\Service;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;

/**
 * 测试用的 LinkGenerator 实现
 */
class TestLinkGenerator implements LinkGeneratorInterface
{
    public function getCurdListPage(string $entityClass): string
    {
        return match ($entityClass) {
            Organization::class => '/admin/organizations',
            UserOrganization::class => '/admin/user-organizations',
            UserOrganizationChangeLog::class => '/admin/user-organization-change-logs',
            default => '',
        };
    }

    public function extractEntityFqcn(string $url): ?string
    {
        return null;
    }

    public function setDashboard(string $dashboardControllerFqcn): void
    {
        // Mock 实现：不需要实际操作
    }
}
