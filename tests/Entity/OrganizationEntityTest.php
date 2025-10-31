<?php

namespace BizOrganizationBundle\Tests\Entity;

use BizOrganizationBundle\Entity\Organization;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Organization::class)]
final class OrganizationEntityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Organization();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Organization'];
        yield 'description' => ['description', 'Test Description'];
        yield 'code' => ['code', 'TEST001'];
        yield 'valid' => ['valid', true];
        yield 'valid_false' => ['valid', false];
        yield 'phone' => ['phone', '123-456-7890'];
        yield 'address' => ['address', 'Test Address'];
        yield 'sortNumber' => ['sortNumber', 10];
    }

    public function testConstruct(): void
    {
        $organization = new Organization();

        $this->assertNull($organization->getId());
        $this->assertSame('', $organization->getName());
        $this->assertNull($organization->getDescription());
        $this->assertNull($organization->getCode());
        $this->assertSame(0, $organization->getSortNumber());
        $this->assertTrue($organization->isValid());
        $this->assertNull($organization->getParent());
        $this->assertCount(0, $organization->getChildren());
        $this->assertNull($organization->getManager());
        $this->assertNull($organization->getPhone());
        $this->assertNull($organization->getAddress());
    }

    public function testToString(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');

        $this->assertSame('Test Organization', (string) $organization);
    }

    public function testIsEnabled(): void
    {
        $organization = new Organization();

        $this->assertTrue($organization->isEnabled());

        $organization->setEnabled(false);
        $this->assertFalse($organization->isEnabled());
        $this->assertFalse($organization->isValid());
    }

    public function testGetLevel(): void
    {
        $organization = new Organization();

        $this->assertSame(0, $organization->getLevel());
    }

    public function testGetFullPath(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');

        $this->assertSame('Test Organization', $organization->getFullPath());
    }

    public function testIsRoot(): void
    {
        $organization = new Organization();

        $this->assertTrue($organization->isRoot());
    }

    public function testIsLeaf(): void
    {
        $organization = new Organization();

        $this->assertTrue($organization->isLeaf());
    }

    public function testGetAllDescendants(): void
    {
        $organization = new Organization();

        $this->assertCount(0, $organization->getAllDescendants());
    }

    public function testItemable(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');

        $this->assertSame(0, $organization->getItemKey());
        $this->assertSame('Test Organization', $organization->getItemLabel());
    }

    public function testArrayConversions(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setDescription('Test Description');
        $organization->setCode('TEST001');
        $organization->setSortNumber(10);
        $organization->setValid(true);
        $organization->setPhone('123-456-7890');
        $organization->setAddress('Test Address');

        $plainArray = $organization->toPlainArray();
        $this->assertIsArray($plainArray);
        $this->assertSame('Test Organization', $plainArray['name']);
        $this->assertSame('Test Description', $plainArray['description']);
        $this->assertSame('TEST001', $plainArray['code']);
        $this->assertSame(10, $plainArray['sortOrder']);
        $this->assertTrue($plainArray['valid']);
        $this->assertSame('123-456-7890', $plainArray['phone']);
        $this->assertSame('Test Address', $plainArray['address']);
        $this->assertTrue($plainArray['isRoot']);
        $this->assertTrue($plainArray['isLeaf']);

        $adminArray = $organization->toAdminArray();
        $this->assertEquals($plainArray, $adminArray);

        $apiArray = $organization->toApiArray();
        $this->assertIsArray($apiArray);
        $this->assertSame('Test Organization', $apiArray['name']);
        $this->assertArrayHasKey('children', $apiArray);
        $this->assertIsArray($apiArray['children']);
    }

    public function testLockEntity(): void
    {
        $organization = new Organization();

        $this->assertSame('organization:', $organization->retrieveLockResource());
    }
}
