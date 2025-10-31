<?php

namespace BizOrganizationBundle\EventListener;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: UserOrganization::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: UserOrganization::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: UserOrganization::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: UserOrganization::class)]
class UserOrganizationChangeListener
{
    /** @var array<string, mixed> */
    private array $changeSet = [];

    public function postPersist(UserOrganization $userOrganization, PostPersistEventArgs $event): void
    {
        $this->createChangeLog(
            $userOrganization,
            'created',
            sprintf(
                '用户 %s 加入组织 %s',
                $userOrganization->getUser()->getUserIdentifier(),
                $userOrganization->getOrganization()->getName()
            ),
            $event
        );
    }

    public function preUpdate(UserOrganization $userOrganization, PreUpdateEventArgs $event): void
    {
        $this->changeSet = $event->getEntityChangeSet();
    }

    public function postUpdate(UserOrganization $userOrganization, PostUpdateEventArgs $event): void
    {
        $changes = [];

        if (isset($this->changeSet['organization'])) {
            $organizationChange = $this->changeSet['organization'];
            assert(is_array($organizationChange) && count($organizationChange) >= 2);
            $oldOrg = $organizationChange[0];
            $newOrg = $organizationChange[1];
            assert($oldOrg instanceof Organization);
            assert($newOrg instanceof Organization);
            $changes[] = sprintf(
                '组织从 %s 变更为 %s',
                $oldOrg->getName(),
                $newOrg->getName()
            );
        }

        if (isset($this->changeSet['isPrimary'])) {
            $isPrimaryChange = $this->changeSet['isPrimary'];
            assert(is_array($isPrimaryChange) && count($isPrimaryChange) >= 2);
            $changes[] = sprintf(
                '主要组织状态变更为 %s',
                (bool) $isPrimaryChange[1] ? '是' : '否'
            );
        }

        if ([] !== $changes) {
            $this->createChangeLog(
                $userOrganization,
                'updated',
                '用户 ' . $userOrganization->getUser()->getUserIdentifier() . ' 的组织关系更新: ' . implode(', ', $changes),
                $event
            );
        }

        $this->changeSet = [];
    }

    public function postRemove(UserOrganization $userOrganization, PostRemoveEventArgs $event): void
    {
        $this->createChangeLog(
            $userOrganization,
            'deleted',
            sprintf(
                '用户 %s 离开组织 %s',
                $userOrganization->getUser()->getUserIdentifier(),
                $userOrganization->getOrganization()->getName()
            ),
            $event
        );
    }

    private function createChangeLog(
        UserOrganization $userOrganization,
        string $action,
        string $content,
        PostPersistEventArgs|PostUpdateEventArgs|PostRemoveEventArgs $event,
    ): void {
        $changeLog = new UserOrganizationChangeLog();
        $changeLog->setUser($userOrganization->getUser());
        $changeLog->setOrganization($userOrganization->getOrganization());
        $changeLog->setContent($content);

        if ('updated' === $action && isset($this->changeSet['organization'])) {
            $organizationChange = $this->changeSet['organization'];
            assert(is_array($organizationChange) && count($organizationChange) >= 2);
            $newOrganization = $organizationChange[1];
            assert($newOrganization instanceof Organization);
            $changeLog->setNewOrganization($newOrganization);
        }

        $entityManager = $event->getObjectManager();
        $entityManager->persist($changeLog);
        $entityManager->flush();
    }
}
