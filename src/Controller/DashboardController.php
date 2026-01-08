<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\DishRepository;
use App\Repository\IngredientRepository;
use App\Repository\RecipeRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage the user dashboard.
 */
class DashboardController extends AbstractController
{
    /**
     * Renders the user dashboard with their dishes and standalone recipes.
     * Supports filtering by search query, tag, or ingredient.
     *
     * @param Request $request
     * @param DishRepository $dishRepository
     * @param RecipeRepository $recipeRepository
     * @param TagRepository $tagRepository
     * @param IngredientRepository $ingredientRepository
     * @return Response
     */
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        DishRepository $dishRepository,
        RecipeRepository $recipeRepository,
        TagRepository $tagRepository,
        IngredientRepository $ingredientRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $query = $request->query->get('q');
        $tagId = $request->query->get('tag');
        $ingredientId = $request->query->get('ingredient');

        $activeTag = $tagId ? $tagRepository->find($tagId) : null;
        $activeIngredient = $ingredientId ? $ingredientRepository->find($ingredientId) : null;

        $dishQb = $dishRepository->createQueryBuilder('d')
            ->leftJoin('d.recipes', 'r')
            ->where('d.user = :user')
            ->setParameter('user', $user)
            ->distinct();

        $recipeQb = $recipeRepository->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.dish IS NULL')
            ->setParameter('user', $user);

        if ($query) {
            $dishQb->andWhere('d.name LIKE :query OR r.title LIKE :query OR r.author LIKE :query')
                ->setParameter('query', '%' . $query . '%');
            
            $recipeQb->andWhere('r.title LIKE :query OR r.author LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($activeTag) {
            $dishQb->innerJoin('r.tags', 't')
                ->andWhere('t.id = :tagId')
                ->setParameter('tagId', $activeTag->getId());

            $recipeQb->innerJoin('r.tags', 't')
                ->andWhere('t.id = :tagId')
                ->setParameter('tagId', $activeTag->getId());
        }

        if ($activeIngredient) {
            $dishQb->innerJoin('r.ingredients', 'i')
                ->andWhere('i.id = :ingredientId')
                ->setParameter('ingredientId', $activeIngredient->getId());

            $recipeQb->innerJoin('r.ingredients', 'i')
                ->andWhere('i.id = :ingredientId')
                ->setParameter('ingredientId', $activeIngredient->getId());
        }

        $dishes = $dishQb->getQuery()->getResult();
        $standaloneRecipes = $recipeQb->getQuery()->getResult();
        
        // Fetch all tags and ingredients used by the user for the sidebar
        $userTags = $tagRepository->createQueryBuilder('t')
            ->join('t.recipes', 'r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->distinct()
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        $userIngredients = $ingredientRepository->createQueryBuilder('i')
            ->join('i.recipes', 'r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->distinct()
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();

        $recipeCount = $user->getRecipes()->count();

        return $this->render('dashboard/index.html.twig', [
            'dishes' => $dishes,
            'standaloneRecipes' => $standaloneRecipes,
            'recipeCount' => $recipeCount,
            'searchQuery' => $query,
            'activeTag' => $activeTag,
            'activeIngredient' => $activeIngredient,
            'userTags' => $userTags,
            'userIngredients' => $userIngredients,
        ]);
    }
}