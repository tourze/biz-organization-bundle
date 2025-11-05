<?php

namespace BizOrganizationBundle\Entity;

use BizOrganizationBundle\Entity\Organization;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity]
#[ORM\Table(name: 'user_organization_change_log', options: ['comment' => '用户组织变动记录表'])]
class UserOrganizationChangeLog
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UserInterface $user = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Organization $organization = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'new_organization_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Organization $newOrganization = null;

    #[ORM\Column(length: 255, options: ['comment' => '变动内容描述'])]
    #[Assert\NotBlank(message: '变动内容不能为空')]
    #[Assert\Length(max: 255, maxMessage: '变动内容不能超过 {{ limit }} 个字符')]
    private ?string $content = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function __toString(): string
    {
        $userName = $this->user?->getUserIdentifier() ?? 'Unknown User';
        $orgName = $this->organization?->getName() ?? 'Unknown Organization';

        return sprintf(
            'User %s organization change: %s',
            $userName,
            $this->content ?? 'No description'
        );
    }

    public function getNewOrganization(): ?Organization
    {
        return $this->newOrganization;
    }

    public function setNewOrganization(?Organization $newOrganization): void
    {
        $this->newOrganization = $newOrganization;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
