<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Directory;
use App\Entity\Item;
use App\Entity\Team;
use App\Entity\User;
use App\Model\Query\UserQuery;
use App\Model\Response\PaginatedList;
use App\Traits\PaginatorTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

class UserRepository extends ServiceEntityRepository
{
    use PaginatorTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByItem(Item $item): ?User
    {
        $list = $item->getParentList();

        return $this->getByList($list);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByList(Directory $list): ?User
    {
        $parent = $list->getParentList();
        if (null !== $parent) {
            return $this->getByList($parent);
        }

        $qb = $this->_em->getRepository(Directory::class)->createQueryBuilder('list');

        return $qb
            ->select('user')
            ->join(User::class, 'user', Join::WITH, 'user.lists = list OR user.inbox = list OR user.trash = list')
            ->where($qb->expr()->eq('list', ':list'))
            ->setParameter('list', $list)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getByQuery(UserQuery $query): PaginatedList
    {
        $teams = [];
        foreach ($query->getUserTeams() as $userTeam) {
            $teams[] = $userTeam->getTeam()->getId();
        }
        $qb = $this->createQueryBuilder('user');
        $qb
            ->join('user.userTeams', 'userTeams')
            ->where($qb->expr()->neq('user', ':userId'))
            ->andWhere('userTeams.team IN(:teams)')
            ->andWhere($qb->expr()->isNotNull('user.publicKey'))
            ->setParameter('teams', $teams)
            ->setParameter('userId', $query->getUser())
            ->setMaxResults($query->getPerPage())
            ->setFirstResult($query->getFirstResult());

        if ($query->name) {
            $qb
                ->andWhere($qb->expr()->like($qb->expr()->lower('user.username'), ':username'))
                ->setParameter('username', '%'.mb_strtolower($query->name).'%');
        }

        return $this->createPaginatedList($qb, $query);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByToken(string $token): ?User
    {
        $qb = $this->createQueryBuilder('user');

        return $qb
            ->where($qb->expr()->eq('user.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneWithPublicKeyByEmail(string $email): ?User
    {
        $qb = $this->createQueryBuilder('user');

        return $qb
            ->where($qb->expr()->eq('user.email', ':email'))
            ->andWhere($qb->expr()->isNotNull('user.publicKey'))
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByEmail(string $email): ?User
    {
        $qb = $this->createQueryBuilder('user');

        return $qb
            ->where($qb->expr()->eq('user.email', ':email'))
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array|User[]
     */
    public function findByTeam(Team $team): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb->innerJoin('user.userTeams', 'userTeams');
        $qb->where('userTeams.team =:team');
        $qb->setParameter('team', $team);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array|User[]
     */
    public function findByIds(array $ids): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb->where('user.id IN(:ids)');
        $qb->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $role
     *
     * @return array|User[]
     */
    public function findAdmins($role = User::ROLE_ADMIN): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb->andWhere($qb->expr()->Like($qb->expr()->lower('user.roles'), ':role'));
        $qb->setParameter('role', '%'.mb_strtolower($role).'%');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array|User[]
     */
    public function findByPartOfEmail(string $partOfEmail): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb->where($qb->expr()->like($qb->expr()->lower('user.email'), ':email'));
        $qb->andWhere($qb->expr()->notLike($qb->expr()->lower('user.roles'), ':role'));
        $qb->setParameter('email', '%'.mb_strtolower($partOfEmail).'%');
        $qb->setParameter('role', '%'.mb_strtolower(User::ROLE_ANONYMOUS_USER).'%');

        return $qb->getQuery()->getResult();
    }

    public function findAllExceptAnonymous(): array
    {
        $qb = $this->createQueryBuilder('user');
        $qb->andWhere($qb->expr()->notLike($qb->expr()->lower('user.roles'), ':role'));
        $qb->setParameter('role', '%'.mb_strtolower(User::ROLE_ANONYMOUS_USER).'%');

        return $qb->getQuery()->getResult();
    }
}
