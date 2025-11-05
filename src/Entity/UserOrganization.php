<?php

namespace BizOrganizationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity]
#[ORM\Table(name: 'user_organization', options: ['comment' => '用户组织关联表'])]
#[ORM\UniqueConstraint(name: 'user_organization_unique', columns: ['user_id', 'organization_id'])]
class UserOrganization
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private UserInterface $user;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'userOrganizations')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Organization $organization;

    #[ORM\Column(name: 'is_primary', type: Types::BOOLEAN, options: ['comment' => '是否主要组织', 'default' => false])]
    #[Assert\Type(type: 'bool')]
    private bool $isPrimary = false;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setPrimary(bool $isPrimary): void
    {
        $this->isPrimary = $isPrimary;
    }

    public function __toString(): string
    {
        $userName = $this->user->getUserIdentifier();
        $organizationName = $this->organization->getName();

        return sprintf(
            'User %s belongs to Organization %s',
            $userName,
            $organizationName
        );
    }
}
