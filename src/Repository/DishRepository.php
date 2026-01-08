<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dish;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dish>
 *
 * @method Dish|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dish|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dish[]    findAll()
 * @method Dish[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DishRepository extends ServiceEntityRepository
{
    use RecipeFilterTrait;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dish::class);
    }

    /**
     * Finds dishes for a user with optional filters.
     *
     * @param User $user
     * @param string|null $query
     * @param iterable $tags
     * @param iterable $ingredients
     * @return Dish[]
     */
    public function findDishesForUser(User $user, ?string $query = null, iterable $tags = [], iterable $ingredients = []): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $user);

        if ($query) {
            $qb->leftJoin('d.recipes', 'r_search')
                ->andWhere('d.name LIKE :query OR r_search.title LIKE :query OR r_search.author LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->distinct();
        }

        $this->applyMandatoryFilter($qb, 'dish', 'd', $tags, 'tags', 'tag');
        $this->applyMandatoryFilter($qb, 'dish', 'd', $ingredients, 'ingredients', 'ing');

        return $qb->getQuery()->getResult();
    }
}