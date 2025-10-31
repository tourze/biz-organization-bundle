<?php

declare(strict_types=1);

namespace BizOrganizationBundle\Tests\Service;

use BizOrganizationBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface $linkGenerator;

    private ItemInterface $item;

    protected function onSetUp(): void
    {
        $this->linkGenerator = new TestLinkGenerator();
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
        $this->item = new TestMenuItem();
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    public function testImplementsMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testInvokeShouldBeCallable(): void
    {
        // AdminMenu实现了__invoke方法，所以是可调用的
        $reflection = new \ReflectionClass(AdminMenu::class);
        $this->assertTrue($reflection->hasMethod('__invoke'));
    }

    public function testInvoke(): void
    {
        // 执行 AdminMenu 的 __invoke 方法
        ($this->adminMenu)($this->item);

        // 验证 "组织管理" 主菜单已创建
        $organizationMenu = $this->item->getChild('组织管理');
        $this->assertInstanceOf(ItemInterface::class, $organizationMenu);

        // 验证子菜单项已创建
        $organizationStructureItem = $organizationMenu->getChild('组织架构');
        $userOrganizationItem = $organizationMenu->getChild('用户组织关联');
        $changeLogItem = $organizationMenu->getChild('变动记录');

        $this->assertInstanceOf(ItemInterface::class, $organizationStructureItem);
        $this->assertInstanceOf(ItemInterface::class, $userOrganizationItem);
        $this->assertInstanceOf(ItemInterface::class, $changeLogItem);

        // 验证子菜单项的URI设置
        $this->assertEquals('/admin/organizations', $organizationStructureItem->getUri());
        $this->assertEquals('/admin/user-organizations', $userOrganizationItem->getUri());
        $this->assertEquals('/admin/user-organization-change-logs', $changeLogItem->getUri());

        // 验证子菜单项的图标属性
        $this->assertEquals('fas fa-sitemap', $organizationStructureItem->getAttribute('icon'));
        $this->assertEquals('fas fa-users-cog', $userOrganizationItem->getAttribute('icon'));
        $this->assertEquals('fas fa-history', $changeLogItem->getAttribute('icon'));
    }
}
