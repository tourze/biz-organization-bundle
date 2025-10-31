<?php

namespace BizOrganizationBundle\Tests\Validation;

use BizOrganizationBundle\Entity\Organization;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ValidatorInterface::class)]
#[RunTestsInSeparateProcesses]
final class OrganizationValidationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试不需要额外的设置
    }

    public function testEntityValidationConstraints(): void
    {
        // 创建空名称的组织实体
        $organization = new Organization();
        $organization->setName(''); // 空名称，应该违反NotBlank约束
        $organization->setValid(true);
        $organization->setSortNumber(1);

        // 获取验证器服务
        $validator = self::getService(ValidatorInterface::class);
        $violations = $validator->validate($organization);

        // 验证存在验证错误
        $this->assertGreaterThan(0, $violations->count(), 'Name字段的NotBlank约束应该产生验证错误');

        // 检查具体的违规内容
        $nameViolations = [];
        foreach ($violations as $violation) {
            if ('name' === $violation->getPropertyPath()) {
                $nameViolations[] = $violation;
            }
        }

        $this->assertGreaterThan(0, count($nameViolations), 'name字段应该有验证错误');
        $this->assertStringContainsString('blank', strtolower($nameViolations[0]->getMessage()));
    }

    public function testEntityFieldLengthValidation(): void
    {
        // 创建超长名称的组织实体
        $organization = new Organization();
        $organization->setName(str_repeat('X', 256)); // 超过255字符限制
        $organization->setCode(str_repeat('Y', 101)); // 超过100字符限制
        $organization->setValid(true);
        $organization->setSortNumber(1);

        // 获取验证器服务
        $validator = self::getService(ValidatorInterface::class);
        $violations = $validator->validate($organization);

        // 验证存在验证错误
        $this->assertGreaterThan(0, $violations->count(), '字段长度约束应该产生验证错误');

        // 检查name字段的长度违规
        $nameViolations = [];
        $codeViolations = [];
        foreach ($violations as $violation) {
            if ('name' === $violation->getPropertyPath()) {
                $nameViolations[] = $violation;
            }
            if ('code' === $violation->getPropertyPath()) {
                $codeViolations[] = $violation;
            }
        }

        $this->assertGreaterThan(0, count($nameViolations), 'name字段应该有长度验证错误');
        $this->assertGreaterThan(0, count($codeViolations), 'code字段应该有长度验证错误');
    }

    public function testValidOrganizationCreation(): void
    {
        // 创建有效的组织实体
        $organization = new Organization();
        $organization->setName('Valid Organization Name');
        $organization->setCode('VALID-001');
        $organization->setDescription('Valid description');
        $organization->setValid(true);
        $organization->setSortNumber(1);

        // 获取验证器服务
        $validator = self::getService(ValidatorInterface::class);
        $violations = $validator->validate($organization);

        // 验证没有验证错误
        $this->assertEquals(0, $violations->count(), '有效的组织实体不应该有验证错误');

        // 测试数据库持久化
        $em = self::getEntityManager();
        $em->persist($organization);
        $em->flush();

        // 验证ID已分配
        $this->assertNotNull($organization->getId());

        // 从数据库重新加载并验证
        $em->clear();
        $savedOrganization = $em->find(Organization::class, $organization->getId());
        $this->assertNotNull($savedOrganization);
        $this->assertEquals('Valid Organization Name', $savedOrganization->getName());
        $this->assertEquals('VALID-001', $savedOrganization->getCode());
    }

    public function testOrganizationEntityConstraints(): void
    {
        // 测试各种无效输入组合
        $testCases = [
            [
                'name' => '',
                'code' => 'TEST',
                'expectedViolations' => ['name'],
                'description' => '空名称应该违反NotBlank约束',
            ],
            [
                'name' => str_repeat('X', 256),
                'code' => 'TEST',
                'expectedViolations' => ['name'],
                'description' => '超长名称应该违反Length约束',
            ],
            [
                'name' => 'Valid Name',
                'code' => str_repeat('Y', 101),
                'expectedViolations' => ['code'],
                'description' => '超长代码应该违反Length约束',
            ],
        ];

        $validator = self::getService(ValidatorInterface::class);

        foreach ($testCases as $testCase) {
            $organization = new Organization();
            $organization->setName($testCase['name']);
            $organization->setCode($testCase['code']);
            $organization->setValid(true);
            $organization->setSortNumber(1);

            $violations = $validator->validate($organization);

            $this->assertGreaterThan(0, $violations->count(), $testCase['description']);

            // 验证具体字段的违规
            foreach ($testCase['expectedViolations'] as $expectedField) {
                $fieldHasViolation = false;
                foreach ($violations as $violation) {
                    if ($violation->getPropertyPath() === $expectedField) {
                        $fieldHasViolation = true;
                        break;
                    }
                }
                $this->assertTrue($fieldHasViolation, "字段 '{$expectedField}' 应该有验证错误");
            }
        }
    }
}
