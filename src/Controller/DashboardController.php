<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\SearchFilterType;
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
     * Supports filtering by search query, multiple tags, and multiple ingredients.
     *
     * @param Request $request
     * @param DishRepository $dishRepository
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        DishRepository $dishRepository,
        RecipeRepository $recipeRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        $searchForm = $this->createForm(SearchFilterType::class);
        $searchForm->handleRequest($request);
        
        $formData = $searchForm->getData() ?? [];
        $query = $formData['q'] ?? null;
        $activeTags = $formData['tags'] ?? [];
        $activeIngredients = $formData['ingredients'] ?? [];

        $dishes = $dishRepository->findDishesForUser($user, $query, $activeTags, $activeIngredients);
        $standaloneRecipes = $recipeRepository->findStandaloneRecipes($user, $query, $activeTags, $activeIngredients);
        
        $recipeCount = $user->getRecipes()->count();

        return $this->render('dashboard/index.html.twig', [
            'dishes' => $dishes,
            'standaloneRecipes' => $standaloneRecipes,
            'recipeCount' => $recipeCount,
            'searchQuery' => $query,
            'activeTags' => $activeTags,
            'activeIngredients' => $activeIngredients,
            'searchForm' => $searchForm,
        ]);
    }
}