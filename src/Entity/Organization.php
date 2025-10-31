<?php

namespace BizOrganizationBundle\Entity;

use BizOrganizationBundle\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineHelper\SortableTrait;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EnumExtra\Itemable;
use Tourze\LockServiceBundle\Model\LockEntity;

/**
 * @implements AdminArrayInterface<string, mixed>
 * @implements PlainArrayInterface<string, mixed>
 * @implements ApiArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'biz_organization', options: ['comment' => '组织机构'])]
class Organization implements Itemable, \Stringable, AdminArrayInterface, PlainArrayInterface, ApiArrayInterface, LockEntity
{
    use TimestampableAware;
    use SnowflakeKeyAware;
    use SortableTrait;

    #[Groups(groups: ['restful_read', 'api_tree', 'admin_curd', 'api_list'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '组织名称'])]
    private string $name = '';

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '组织描述'])]
    private ?string $description = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[IndexColumn]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '组织编码'])]
    private ?string $code = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[IndexColumn]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    private bool $valid = true;

    #[Groups(groups: ['restful_read', 'api_tree', 'admin_curd'])]
    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['persist'], inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[Groups(groups: ['restful_read', 'api_tree'])]
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\OrderBy(value: ['sortNumber' => 'ASC', 'name' => 'ASC'])]
    private Collection $children;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UserInterface $manager = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '联系电话'])]
    private ?string $phone = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '办公地址'])]
    private ?string $address = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getFullPath();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    public function isEnabled(): bool
    {
        return $this->valid;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->valid = $enabled;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getChildrenCount(): ?int
    {
        return $this->children->count();
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getManager(): ?UserInterface
    {
        return $this->manager;
    }

    public function setManager(?UserInterface $manager): void
    {
        $this->manager = $manager;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;
        while (null !== $parent) {
            ++$level;
            $parent = $parent->getParent();
        }

        return $level;
    }

    public function getFullPath(): string
    {
        $path = [];
        $current = $this;
        while (null !== $current) {
            array_unshift($path, $current->getName());
            $current = $current->getParent();
        }

        return implode(' > ', $path);
    }

    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    /**
     * @return self[]
     */
    public function getAllDescendants(): array
    {
        $descendants = [];
        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getAllDescendants());
        }

        return $descendants;
    }

    public function getItemKey(): int|string
    {
        return $this->id ?? 0;
    }

    public function getItemLabel(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlainArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'sortOrder' => $this->sortNumber,
            'valid' => $this->valid,
            'parentId' => $this->parent?->getId(),
            'managerId' => (null !== $this->manager && method_exists($this->manager, 'getId')) ? $this->manager->getId() : null,
            'phone' => $this->phone,
            'address' => $this->address,
            'level' => $this->getLevel(),
            'fullPath' => $this->getFullPath(),
            'isRoot' => $this->isRoot(),
            'isLeaf' => $this->isLeaf(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return $this->toPlainArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'sortOrder' => $this->sortNumber,
            'valid' => $this->valid,
            'parentId' => $this->parent?->getId(),
            'manager' => (null !== $this->manager) ? [
                'id' => method_exists($this->manager, 'getId') ? $this->manager->getId() : null,
                'username' => $this->manager->getUserIdentifier(),
            ] : null,
            'phone' => $this->phone,
            'address' => $this->address,
            'level' => $this->getLevel(),
            'fullPath' => $this->getFullPath(),
            'children' => array_map(fn ($child) => $child->toApiArray(), $this->children->toArray()),
        ];
    }

    public function retrieveLockResource(): string
    {
        return 'organization:' . $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSelectItem(): array
    {
        return [
            'value' => $this->getItemKey(),
            'label' => $this->getItemLabel(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return $this->toAdminArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrievePlainArray(): array
    {
        return $this->toPlainArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return $this->toApiArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->toPlainArray();
    }
}
