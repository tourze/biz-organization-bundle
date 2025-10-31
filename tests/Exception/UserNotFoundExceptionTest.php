<?php

namespace BizOrganizationBundle\Tests\Exception;

use BizOrganizationBundle\Exception\UserNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(UserNotFoundException::class)]
final class UserNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultMessage(): void
    {
        $exception = new UserNotFoundException();

        $this->assertSame('Required users not found for fixtures', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertInstanceOf(\LogicException::class, $exception);
    }

    public function testCustomMessage(): void
    {
        $exception = new UserNotFoundException('Custom message');

        $this->assertSame('Custom message', $exception->getMessage());
    }

    public function testCustomCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new UserNotFoundException('Test message', 123, $previous);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
