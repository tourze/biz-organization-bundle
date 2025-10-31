<?php

declare(strict_types=1);

namespace BizOrganizationBundle\Exception;

class UserNotFoundException extends \LogicException
{
    public function __construct(string $message = 'Required users not found for fixtures', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
