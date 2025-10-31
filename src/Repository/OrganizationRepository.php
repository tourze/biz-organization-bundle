<?php

namespace BizOrganizationBundle\Repository;

use BizOrganizationBundle\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Organization>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: Organization::class)]
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * @return Organization[]
     */
    public function findByValid(bool $valid = true): array
    {
        return $this->findBy(['valid' => $valid], ['sortNumber' => 'ASC', 'name' => 'ASC']);
    }

    /**
     * @return array<Organization>
     */
    public function findRootOrganizations(): array
    {
        /** @var array<Organization> */
        return $this->createQueryBuilder('o')
            ->where('o.parent IS NULL')
            ->andWhere('o.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('o.sortNumber', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Organization>
     */
    public function findByParent(?Organization $parent): array
    {
        $qb = $this->createQueryBuilder('o');

        if (null === $parent) {
            $qb->where('o.parent IS NULL');
        } else {
            $qb->where('o.parent = :parent')
                ->setParameter('parent', $parent)
            ;
        }

        /** @var array<Organization> */
        return $qb->andWhere('o.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('o.sortNumber', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByCode(string $code): ?Organization
    {
        return $this->findOneBy(['code' => $code, 'valid' => true]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findTreeStructure(): array
    {
        $rootOrganizations = $this->findRootOrganizations();

        return $this->buildTree($rootOrganizations);
    }

    /**
     * @param Organization[] $organizations
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(array $organizations): array
    {
        $tree = [];
        foreach ($organizations as $organization) {
            $node = [
                'organization' => $organization,
                'children' => $this->buildTree($organization->getChildren()->toArray()),
            ];
            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * @return array<Organization>
     */
    public function findAllDescendants(Organization $organization): array
    {
        /** @var array<Organization> */
        return $this->createQueryBuilder('o')
            ->where('o.parent = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Organization>
     */
    public function findByLevel(int $level): array
    {
        $qb = $this->createQueryBuilder('o');

        for ($i = 0; $i < $level; ++$i) {
            if (0 === $i) {
                $qb->leftJoin('o.parent', 'p' . $i);
            } else {
                $qb->leftJoin('p' . ($i - 1) . '.parent', 'p' . $i);
            }
        }

        if (0 === $level) {
            $qb->where('o.parent IS NULL');
        } else {
            $qb->where('p' . ($level - 1) . ' IS NOT NULL');
            if ($level > 1) {
                $qb->andWhere('p' . $level . ' IS NULL');
            }
        }

        /** @var array<Organization> */
        return $qb->andWhere('o.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('o.sortNumber', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<Organization>
     */
    public function findByName(string $name): array
    {
        /** @var array<Organization> */
        return $this->createQueryBuilder('o')
            ->where('o.name LIKE :name')
            ->andWhere('o.valid = :valid')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('valid', true)
            ->orderBy('o.sortNumber', 'ASC')
            ->addOrderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countByParent(?Organization $parent): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
        ;

        if (null === $parent) {
            $qb->where('o.parent IS NULL');
        } else {
            $qb->where('o.parent = :parent')
                ->setParameter('parent', $parent)
            ;
        }

        return (int) $qb->andWhere('o.valid = :valid')
            ->setParameter('valid', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return Organization[]
     */
    public function findByEnabled(bool $enabled = true): array
    {
        return $this->findBy(['valid' => $enabled], ['sortNumber' => 'ASC', 'name' => 'ASC']);
    }

    public function save(Organization $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Organization $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
