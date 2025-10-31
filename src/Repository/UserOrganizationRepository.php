<?php

namespace BizOrganizationBundle\Repository;

use BizOrganizationBundle\Entity\UserOrganization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<UserOrganization>
 */
#[AsRepository(entityClass: UserOrganization::class)]
class UserOrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserOrganization::class);
    }

    public function save(UserOrganization $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserOrganization $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找用户所属的所有组织
     *
     * @param UserInterface $user 用户对象
     * @return UserOrganization[]
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * 查找用户的主要组织
     *
     * @param UserInterface $user 用户对象
     * @return UserOrganization|null
     */
    public function findPrimaryByUser(UserInterface $user): ?UserOrganization
    {
        return $this->findOneBy(['user' => $user, 'isPrimary' => true]);
    }

    /**
     * 查找组织下的所有用户
     *
     * @param string $organizationId 组织ID
     * @return array<UserOrganization>
     */
    public function findByOrganizationId(string $organizationId): array
    {
        /** @var array<UserOrganization> */
        return $this->createQueryBuilder('uo')
            ->join('uo.organization', 'o')
            ->where('o.id = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找用户在指定组织及其子组织中的关联
     *
     * @param UserInterface $user 用户对象
     * @param string $organizationId 组织ID
     * @return array<UserOrganization>
     */
    public function findByUserAndOrganizationHierarchy(UserInterface $user, string $organizationId): array
    {
        /** @var array<UserOrganization> */
        return $this->createQueryBuilder('uo')
            ->join('uo.organization', 'o')
            ->join('BizOrganizationBundle\Entity\Organization', 'child', 'WITH', 'o.id = child.id OR child.parent = o')
            ->where('uo.user = :user')
            ->andWhere('o.id = :organizationId OR o.parent IN (
                SELECT p.id FROM BizOrganizationBundle\Entity\Organization p
                WHERE p.parent = :organizationId
            )')
            ->setParameter('user', $user)
            ->setParameter('organizationId', $organizationId)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计用户在组织中的数量
     *
     * @param string $organizationId 组织ID
     * @return int
     */
    public function countUsersByOrganizationId(string $organizationId): int
    {
        $result = $this->createQueryBuilder('uo')
            ->select('COUNT(uo.id)')
            ->join('uo.organization', 'o')
            ->where('o.id = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 删除用户在指定组织的关联
     *
     * @param UserInterface $user 用户对象
     * @param string $organizationId 组织ID
     * @return int 删除的记录数
     */
    public function removeByUserAndOrganizationId(UserInterface $user, string $organizationId): int
    {
        /** @var int */
        return $this->createQueryBuilder('uo')
            ->delete()
            ->where('uo.user = :user')
            ->andWhere('uo.organization = :organizationId')
            ->setParameter('user', $user)
            ->setParameter('organizationId', $organizationId)
            ->getQuery()
            ->execute()
        ;
    }
}
