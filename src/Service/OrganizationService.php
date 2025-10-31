<?php

namespace BizOrganizationBundle\Service;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Exception\OrganizationException;
use BizOrganizationBundle\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;

#[Autoconfigure(public: true)]
readonly class OrganizationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizationRepository $organizationRepository,
    ) {
    }

    public function createOrganization(
        string $name,
        ?string $description = null,
        ?string $code = null,
        ?Organization $parent = null,
        ?UserInterface $manager = null,
        int $sortOrder = 0,
    ): Organization {
        $organization = new Organization();
        $organization->setName($name);
        $organization->setDescription($description);
        $organization->setCode($code);
        $organization->setParent($parent);
        $organization->setManager($manager);
        $organization->setSortNumber($sortOrder);

        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        return $organization;
    }

    public function updateOrganization(
        Organization $organization,
        ?string $name = null,
        ?string $description = null,
        ?string $code = null,
        ?Organization $parent = null,
        ?UserInterface $manager = null,
        ?int $sortOrder = null,
        ?bool $valid = null,
    ): Organization {
        if (null !== $name) {
            $organization->setName($name);
        }
        if (null !== $description) {
            $organization->setDescription($description);
        }
        if (null !== $code) {
            $organization->setCode($code);
        }
        if (null !== $parent) {
            $this->validateParentChange($organization, $parent);
            $organization->setParent($parent);
        }
        if (null !== $manager) {
            $organization->setManager($manager);
        }
        if (null !== $sortOrder) {
            $organization->setSortNumber($sortOrder);
        }
        if (null !== $valid) {
            $organization->setValid($valid);
        }

        $this->entityManager->flush();

        return $organization;
    }

    public function deleteOrganization(Organization $organization, bool $force = false): void
    {
        if (!$force && !$organization->getChildren()->isEmpty()) {
            throw OrganizationException::cannotDeleteWithChildren();
        }

        if ($force) {
            foreach ($organization->getChildren() as $child) {
                $this->deleteOrganization($child, true);
            }
        }

        $this->entityManager->remove($organization);
        $this->entityManager->flush();
    }

    public function moveOrganization(Organization $organization, ?Organization $newParent): void
    {
        $this->validateParentChange($organization, $newParent);
        $organization->setParent($newParent);
        $this->entityManager->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOrganizationTree(): array
    {
        return $this->organizationRepository->findTreeStructure();
    }

    /**
     * @return Organization[]
     */
    public function getOrganizationPath(Organization $organization): array
    {
        $path = [];
        $current = $organization;
        while (null !== $current) {
            array_unshift($path, $current);
            $current = $current->getParent();
        }

        return $path;
    }

    /**
     * @return Organization[]
     */
    public function findOrganizationsByManager(UserInterface $manager): array
    {
        return $this->organizationRepository->findBy(['manager' => $manager, 'valid' => true]);
    }

    /**
     * @return Organization[]
     */
    public function getSubordinateOrganizations(Organization $organization, bool $includeDisabled = false): array
    {
        $criteria = ['parent' => $organization];
        if (!$includeDisabled) {
            $criteria['valid'] = true;
        }

        return $this->organizationRepository->findBy($criteria, ['sortNumber' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * @return Organization[]
     */
    public function getAllSubordinateOrganizations(Organization $organization, bool $includeDisabled = false): array
    {
        $allSubordinates = [];
        $directSubordinates = $this->getSubordinateOrganizations($organization, $includeDisabled);

        foreach ($directSubordinates as $subordinate) {
            $allSubordinates[] = $subordinate;
            $allSubordinates = array_merge(
                $allSubordinates,
                $this->getAllSubordinateOrganizations($subordinate, $includeDisabled)
            );
        }

        return $allSubordinates;
    }

    public function isAncestor(Organization $ancestor, Organization $descendant): bool
    {
        $current = $descendant->getParent();
        while (null !== $current) {
            if ($current === $ancestor) {
                return true;
            }
            $current = $current->getParent();
        }

        return false;
    }

    public function findCommonAncestor(Organization $org1, Organization $org2): ?Organization
    {
        $path1 = $this->getOrganizationPath($org1);
        $path2 = $this->getOrganizationPath($org2);

        $commonAncestor = null;
        $minLength = min(count($path1), count($path2));

        for ($i = 0; $i < $minLength; ++$i) {
            if ($path1[$i] === $path2[$i]) {
                $commonAncestor = $path1[$i];
            } else {
                break;
            }
        }

        return $commonAncestor;
    }

    /**
     * @return Organization[]
     */
    public function searchOrganizations(string $keyword): array
    {
        return $this->organizationRepository->findByName($keyword);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganizationStatistics(Organization $organization): array
    {
        $directChildren = $this->getSubordinateOrganizations($organization);
        $allDescendants = $this->getAllSubordinateOrganizations($organization);

        return [
            'id' => $organization->getId(),
            'name' => $organization->getName(),
            'level' => $organization->getLevel(),
            'directChildrenCount' => count($directChildren),
            'totalDescendantsCount' => count($allDescendants),
            'hasManager' => null !== $organization->getManager(),
            'managerName' => $organization->getManager()?->getUserIdentifier(),
            'isRoot' => $organization->isRoot(),
            'isLeaf' => $organization->isLeaf(),
            'fullPath' => $organization->getFullPath(),
        ];
    }

    private function validateParentChange(Organization $organization, ?Organization $newParent): void
    {
        if (null === $newParent) {
            return;
        }

        if ($newParent === $organization) {
            throw OrganizationException::cannotSetSelfAsParent();
        }

        if ($this->isAncestor($organization, $newParent)) {
            throw OrganizationException::circularReferenceNotAllowed();
        }
    }
}
