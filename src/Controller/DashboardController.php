<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     *
     * @param RecipeRepository $recipeRepository
     * @return Response
     */
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(RecipeRepository $recipeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $dishes = $user->getDishes();
        
        // Fetch recipes without a dish
        $standaloneRecipes = $recipeRepository->findBy([
            'user' => $user,
            'dish' => null,
        ]);

        $recipeCount = $user->getRecipes()->count();

        return $this->render('dashboard/index.html.twig', [
            'dishes' => $dishes,
            'standaloneRecipes' => $standaloneRecipes,
            'recipeCount' => $recipeCount,
        ]);
    }
}
