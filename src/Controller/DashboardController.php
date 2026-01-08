<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\DishRepository;
use App\Repository\RecipeRepository;
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
     * Supports filtering by a search query.
     *
     * @param Request $request
     * @param DishRepository $dishRepository
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, DishRepository $dishRepository, RecipeRepository $recipeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $query = $request->query->get('q');

        if ($query) {
            $dishes = $dishRepository->createQueryBuilder('d')
                ->leftJoin('d.recipes', 'r')
                ->where('d.user = :user')
                ->andWhere('d.name LIKE :query OR r.title LIKE :query OR r.author LIKE :query')
                ->setParameter('user', $user)
                ->setParameter('query', '%' . $query . '%')
                ->distinct()
                ->getQuery()
                ->getResult();

            $standaloneRecipes = $recipeRepository->createQueryBuilder('r')
                ->where('r.user = :user')
                ->andWhere('r.dish IS NULL')
                ->andWhere('r.title LIKE :query OR r.author LIKE :query')
                ->setParameter('user', $user)
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        } else {
            $dishes = $user->getDishes();
            $standaloneRecipes = $recipeRepository->findBy([
                'user' => $user,
                'dish' => null,
            ]);
        }

        $recipeCount = $user->getRecipes()->count();

        return $this->render('dashboard/index.html.twig', [
            'dishes' => $dishes,
            'standaloneRecipes' => $standaloneRecipes,
            'recipeCount' => $recipeCount,
            'searchQuery' => $query,
        ]);
    }
}