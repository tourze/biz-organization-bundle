<?php

namespace BizOrganizationBundle\Controller\Admin;

use BizOrganizationBundle\Entity\Organization;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\UserServiceContracts\UserManagerInterface;

/**
 * @extends AbstractCrudController<Organization>
 */
#[AdminCrud(routePath: '/biz-organization/organization', routeName: 'biz_organization_organization')]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class OrganizationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Organization::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('组织')
            ->setEntityLabelInPlural('组织管理')
            ->setPageTitle('index', '组织列表')
            ->setPageTitle('new', '新建组织')
            ->setPageTitle('edit', '编辑组织')
            ->setPageTitle('detail', '组织详情')
            ->setSearchFields(['name', 'code', 'description'])
            ->setDefaultSort(['sortNumber' => 'ASC', 'name' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // 预加载子组织数据以优化性能，避免N+1查询
        $qb->leftJoin('entity.children', 'children')
            ->addSelect('children')
        ;

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '组织名称'))
            ->add(TextFilter::new('code', '组织编码'))
            ->add(EntityFilter::new('parent', '上级组织'))
            ->add(EntityFilter::new('manager', '负责人'))
            ->add(BooleanFilter::new('valid', '有效状态'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->getCommonFields();
        yield from $this->getDetailOnlyFields($pageName);
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getCommonFields(): iterable
    {
        yield IdField::new('id', '编号')
            ->onlyOnDetail()
        ;

        yield TextField::new('name', '组织名称')
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield TextField::new('code', '组织编码')
            ->setColumns(6)
            ->setHelp('用于系统识别的唯一编码')
            ->formatValue(fn ($value) => null !== $value && '' !== $value ? $value : '-')
        ;

        yield TextareaField::new('description', '描述')
            ->setNumOfRows(3)
            ->onlyOnForms()
            ->formatValue(fn ($value) => null !== $value && '' !== $value ? $value : '-')
        ;

        yield AssociationField::new('parent', '上级组织')
            ->setColumns(5)
            ->autocomplete()
            ->formatValue($this->formatParentValue())
        ;

        yield AssociationField::new('manager', '负责人')
            ->setColumns(4)
            ->autocomplete()
            ->formatValue($this->formatManagerValue())
        ;

        yield IntegerField::new('childrenCount', '子组织数量')
            ->setColumns(2)
            ->formatValue($this->formatChildrenCountValue())
            ->hideOnForm()
        ;

        yield TextField::new('phone', '联系电话')
            ->setColumns(5)
            ->onlyOnForms()
        ;

        yield TextField::new('address', '办公地址')
            ->setColumns(7)
            ->onlyOnForms()
        ;

        yield BooleanField::new('valid', '有效状态')
            ->setColumns(3)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getDetailOnlyFields(string $pageName): iterable
    {
        if (Crud::PAGE_DETAIL !== $pageName) {
            return;
        }

        yield TextField::new('fullPath', '完整路径')
            ->setColumns(12)
        ;

        yield IntegerField::new('level', '层级')
            ->setColumns(3)
        ;
    }

    private function formatParentValue(): callable
    {
        return function ($value, $entity): string {
            if (!$entity instanceof Organization) {
                return '-';
            }
            $parent = $entity->getParent();

            return null !== $parent ? $parent->getName() : '-';
        };
    }

    private function formatManagerValue(): callable
    {
        return function ($value, $entity): string {
            if (!$entity instanceof Organization) {
                return '-';
            }
            $manager = $entity->getManager();

            return null !== $manager ? $manager->getUserIdentifier() : '-';
        };
    }

    private function formatChildrenCountValue(): callable
    {
        return function ($value, $entity): int {
            if (!$entity instanceof Organization) {
                return 0;
            }

            return $entity->getChildren()->count();
        };
    }

    public function autocomplete(AdminContext $context): JsonResponse
    {
        $request = $context->getRequest();
        $property = $request->query->get('property');

        if ('manager' === $property) {
            $query = (string) $request->query->get('q', '');
            $results = $this->userManager->searchUsers($query, 20);

            return new JsonResponse(['results' => $results]);
        }

        return parent::autocomplete($context);
    }
}
