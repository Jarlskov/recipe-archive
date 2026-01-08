<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 *
 * @method Recipe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recipe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recipe[]    findAll()
 * @method Recipe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipeRepository extends ServiceEntityRepository
{
    use RecipeFilterTrait;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     * Finds standalone recipes (without a dish) for a user with optional filters.
     *
     * @param User $user
     * @param string|null $query
     * @param iterable $tags
     * @param iterable $ingredients
     * @return Recipe[]
     */
    public function findStandaloneRecipes(User $user, ?string $query = null, iterable $tags = [], iterable $ingredients = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.dish IS NULL')
            ->setParameter('user', $user);

        if ($query) {
            $qb->andWhere('r.title LIKE :query OR r.author LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        $this->applyMandatoryFilter($qb, 'id', 'r.id', $tags, 'tags', 'tag');
        $this->applyMandatoryFilter($qb, 'id', 'r.id', $ingredients, 'ingredients', 'ing');

        return $qb->getQuery()->getResult();
    }
}