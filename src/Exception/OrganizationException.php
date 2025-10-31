<?php

namespace BizOrganizationBundle\Exception;

class OrganizationException extends \RuntimeException
{
    public static function cannotSetSelfAsParent(): self
    {
        return new self('组织不能设置自己为父组织');
    }

    public static function cannotDeleteWithChildren(): self
    {
        return new self('不能删除有子组织的组织，请先删除子组织或使用强制删除');
    }

    public static function circularReferenceNotAllowed(): self
    {
        return new self('不能将组织设置为其子组织的父组织，这会形成循环引用');
    }
}
