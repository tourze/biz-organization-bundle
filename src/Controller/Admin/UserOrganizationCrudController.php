<?php

namespace BizOrganizationBundle\Controller\Admin;

use BizOrganizationBundle\Entity\Organization;
use BizOrganizationBundle\Entity\UserOrganization;
use BizOrganizationBundle\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @extends AbstractCrudController<UserOrganization>
 */
#[AdminCrud(routePath: '/organization/user_organization', routeName: 'organization_user_organization')]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class UserOrganizationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationRepository $organizationRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return UserOrganization::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('用户组织关联')
            ->setEntityLabelInPlural('用户组织关联管理')
            ->setPageTitle('index', '用户组织关联列表')
            ->setPageTitle('new', '新增')
            ->setPageTitle('edit', '编辑用户组织关联')
            ->setPageTitle('detail', '用户组织关联详情')
            ->setSearchFields(['user.username', 'user.nickName', 'organization.name'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addHtmlContentToHead($this->getNullValueStyles())
            ->addHtmlContentToBody($this->getNullValueScript())
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::EDIT)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user', '用户'))
            ->add(EntityFilter::new('organization', '组织'))
            ->add(BooleanFilter::new('isPrimary', '是否主要组织'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '编号')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('user', '用户')
            ->setRequired(true)
            ->setColumns(6)
            ->formatValue($this->formatUserValue())
            ->setHelp('选择要关联的用户')
        ;

        yield AssociationField::new('organization', '组织')
            ->setRequired(true)
            ->setColumns(6)
            ->autocomplete()
            ->setQueryBuilder(
                fn (QueryBuilder $queryBuilder) => $queryBuilder
                    ->andWhere('entity.valid = true')
                    ->orderBy('entity.sortNumber', 'ASC')
                    ->addOrderBy('entity.name', 'ASC')
            )
            ->setHelp('选择要关联的组织')
        ;

        yield BooleanField::new('isPrimary', '是否主要组织')
            ->setColumns(6)
            ->setHelp('标记这是否为用户的主要组织关联。一个用户可以有多个组织关联，但只能有一个主要组织。')
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            yield DateTimeField::new('createTime', '创建时间')
                ->setColumns(3)
                ->setFormat('yyyy-MM-dd HH:mm')
            ;

            yield DateTimeField::new('updateTime', '更新时间')
                ->setColumns(3)
                ->setFormat('yyyy-MM-dd HH:mm')
            ;
        }

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
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

    public function autocomplete(AdminContext $context): JsonResponse
    {
        $request = $context->getRequest();
        $property = $request->query->get('property');
        $query = is_string($request->query->get('q')) ? $request->query->get('q') : null;

        return match ($property) {
            'user' => $this->autocompleteUsers($query),
            'organization' => $this->autocompleteOrganizations($query),
            default => parent::autocomplete($context),
        };
    }

    private function autocompleteUsers(?string $query): JsonResponse
    {
        $connection = $this->entityManager->getConnection();

        $sql = 'SELECT id, username, nick_name FROM biz_user WHERE valid = ? ORDER BY username ASC';
        $params = [1];

        if (null !== $query && '' !== $query) {
            $sql = 'SELECT id, username, nick_name FROM biz_user WHERE valid = ? AND (username LIKE ? OR nick_name LIKE ?) ORDER BY username ASC';
            $params = [1, '%' . $query . '%', '%' . $query . '%'];
        }

        $result = $connection->executeQuery($sql, $params);
        $users = $result->fetchAllAssociative();

        $results = [];
        foreach ($users as $user) {
            $username = is_string($user['username']) ? $user['username'] : '';
            $nickname = $user['nick_name'] ?? null;
            $nicknameStr = is_string($nickname) ? $nickname : '';
            $results[] = [
                'id' => $user['id'],
                'text' => $username . ('' !== $nicknameStr ? ' (' . $nicknameStr . ')' : ''),
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    private function autocompleteOrganizations(?string $query): JsonResponse
    {
        $qb = $this->organizationRepository->createQueryBuilder('o')
            ->where('o.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('o.sortNumber', 'ASC')
            ->addOrderBy('o.name', 'ASC')
        ;

        $this->addOrganizationSearchConditions($qb, $query);

        /** @var array<Organization> $organizations */
        $organizations = $qb->getQuery()->getResult();
        $results = [];
        foreach ($organizations as $organization) {
            $results[] = $this->formatOrganizationResult($organization);
        }

        return new JsonResponse(['results' => $results]);
    }

    private function addOrganizationSearchConditions(QueryBuilder $qb, ?string $query): void
    {
        if (null !== $query && '' !== $query) {
            $qb->andWhere('o.name LIKE :query OR o.code LIKE :query')
                ->setParameter('query', '%' . $query . '%')
            ;
        }
    }

    /**
     * @return array{id: string|null, text: string}
     */
    private function formatOrganizationResult(Organization $organization): array
    {
        return [
            'id' => $organization->getId(),
            'text' => $organization->getFullPath() . (null !== $organization->getCode() ? ' [' . $organization->getCode() . ']' : ''),
        ];
    }

    private function getNullValueStyles(): string
    {
        return '<style>
/* 隐藏显示为 null 的文本 */
td:contains("null"), 
.field-text:contains("null"),
.field-association:contains("null") {
    color: #6c757d;
}
</style>';
    }

    private function getNullValueScript(): string
    {
        return '<script>
document.addEventListener("DOMContentLoaded", function() {
    // 替换页面中的 null 值为 -
    function replaceNullValues() {
        const elements = document.querySelectorAll("td, .field-text, .field-association, .field-integer, .field-datetime, .field-boolean");
        elements.forEach(function(element) {
            if (element.textContent && element.textContent.trim().toLowerCase() === "null") {
                element.textContent = "-";
                element.style.color = "#6c757d";
            }
        });
    }
    
    // 页面加载完成后替换
    replaceNullValues();
    
    // 监听Ajax请求完成后也进行替换
    let observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                setTimeout(replaceNullValues, 100);
            }
        });
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>';
    }

    /**
     * 格式化用户字段显示值
     */
    private function formatUserValue(): callable
    {
        return function ($value, $entity): string {
            if (!$entity instanceof UserOrganization) {
                return '-';
            }
            $user = $entity->getUser();

            return null !== $user ? $user->getUserIdentifier() : '-';
        };
    }
}
