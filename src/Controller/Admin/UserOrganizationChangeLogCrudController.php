<?php

namespace BizOrganizationBundle\Controller\Admin;

use BizOrganizationBundle\Entity\UserOrganizationChangeLog;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * @extends AbstractCrudController<UserOrganizationChangeLog>
 */
#[AdminCrud(routePath: '/biz-organization/user-organization-change-log', routeName: 'biz_organization_user_organization_change_log')]
final class UserOrganizationChangeLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserOrganizationChangeLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('用户组织变动记录')
            ->setEntityLabelInPlural('用户组织变动记录管理')
            ->setSearchFields(['user.username', 'user.nickName', 'organization.name', 'content'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user', '用户'))
            ->add(EntityFilter::new('organization', '原组织'))
            ->add(EntityFilter::new('newOrganization', '新组织'))
            ->add(TextFilter::new('content', '变动内容'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('user', '用户')
            ->setColumns(6)
            ->formatValue($this->formatUserValue())
        ;

        yield AssociationField::new('organization', '原组织')
            ->setColumns(6)
        ;

        yield AssociationField::new('newOrganization', '新组织')
            ->setColumns(6)
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            yield TextField::new('content', '变动内容')
                ->setColumns(6)
                ->setMaxLength(50)
            ;

            yield DateTimeField::new('createTime', '变动时间')
                ->setColumns(3)
                ->setFormat('yyyy-MM-dd HH:mm')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
            yield TextareaField::new('content', '变动内容')
                ->setColumns(12)
                ->setNumOfRows(3)
                ->setHelp('描述用户组织变动的详细信息')
            ;

            yield DateTimeField::new('createTime', '创建时间')
                ->setColumns(6)
                ->setFormat('yyyy-MM-dd HH:mm:ss')
                ->onlyOnDetail()
            ;

            yield DateTimeField::new('updateTime', '更新时间')
                ->setColumns(6)
                ->setFormat('yyyy-MM-dd HH:mm:ss')
                ->onlyOnDetail()
            ;
        }
    }

    private function formatUserValue(): callable
    {
        return function ($value, $entity): string {
            if (!$entity instanceof UserOrganizationChangeLog) {
                return '-';
            }
            $user = $entity->getUser();

            return null !== $user ? $user->getUserIdentifier() : '-';
        };
    }
}
