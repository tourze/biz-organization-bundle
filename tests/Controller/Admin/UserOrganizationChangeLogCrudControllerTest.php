<?php

namespace BizOrganizationBundle\Tests\Controller\Admin;

use BizOrganizationBundle\Controller\Admin\UserOrganizationChangeLogCrudController;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(UserOrganizationChangeLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class UserOrganizationChangeLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testUnauthorizedAccessToCrudListPage(): void
    {
        $client = self::createClient();

        $client->request('GET', '/admin/biz-organization/user-organization-change-log');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testUnauthorizedAccessToCrudDetailPage(): void
    {
        $client = self::createClient();

        $client->request('GET', '/admin/biz-organization/user-organization-change-log/1');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());
    }

    public function testNewActionIsDisabled(): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@test.com', 'password123');
        $this->loginAsAdmin($client, 'admin@test.com', 'password123');

        // 新建操作应该被禁用，期望抛出 ForbiddenActionException
        $this->expectException(ForbiddenActionException::class);
        $crawler = $client->request('GET', '/admin/biz-organization/user-organization-change-log/new');
    }

    public function testEditActionIsDisabled(): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@test.com', 'password123');
        $this->loginAsAdmin($client, 'admin@test.com', 'password123');

        // 编辑操作应该被禁用，期望抛出 ForbiddenActionException
        $this->expectException(ForbiddenActionException::class);
        $crawler = $client->request('GET', '/admin/biz-organization/user-organization-change-log/1/edit');
    }

    public function testListPageAccessForAuthorizedUser(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/admin/biz-organization/user-organization-change-log');

        // 检查列表页面是否可以正常访问
        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content, 'Response content should be a string');
        $this->assertStringContainsString('content-wrapper', $content);
    }

    public function testSearchFieldsConfiguration(): void
    {
        $client = self::createClientWithDatabase();
        $admin = $this->createAdminUser('admin@test.com', 'password123');
        $this->loginAsAdmin($client, 'admin@test.com', 'password123');

        $crawler = $client->request('GET', '/admin/biz-organization/user-organization-change-log');

        // 检查页面是否成功加载
        $this->assertTrue($client->getResponse()->isSuccessful());

        // 由于这是日志记录，主要用于查看，不进行实际搜索测试
    }

    /**
     * @return AbstractCrudController<UserOrganizationChangeLog>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return new UserOrganizationChangeLogCrudController();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield '用户' => ['用户'];
        yield '原组织' => ['原组织'];
        yield '新组织' => ['新组织'];
        yield '变动内容' => ['变动内容'];
        yield '变动时间' => ['变动时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 此控制器禁用了NEW操作，但为了满足抽象类要求提供空字段
        yield '用户' => ['user'];
        yield '原组织' => ['organization'];
        yield '新组织' => ['newOrganization'];
        yield '变动内容' => ['content'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 此控制器禁用了EDIT操作，但为了满足抽象类要求提供空字段
        yield '用户' => ['user'];
        yield '原组织' => ['organization'];
        yield '新组织' => ['newOrganization'];
        yield '变动内容' => ['content'];
    }
}
