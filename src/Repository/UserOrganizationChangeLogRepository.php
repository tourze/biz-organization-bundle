<?php

namespace BizOrganizationBundle\Repository;

use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<UserOrganizationChangeLog>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: UserOrganizationChangeLog::class)]
class UserOrganizationChangeLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserOrganizationChangeLog::class);
    }

    public function save(UserOrganizationChangeLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserOrganizationChangeLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找用户的组织变动记录
     *
     * @param UserInterface $user 用户对象
     * @return UserOrganizationChangeLog[]
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
