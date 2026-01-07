<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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
     * Renders the user dashboard with their dishes and some stats.
     *
     * @return Response
     */
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $dishes = $user->getDishes();
        
        $recipeCount = 0;
        foreach ($dishes as $dish) {
            $recipeCount += $dish->getRecipes()->count();
        }

        return $this->render('dashboard/index.html.twig', [
            'dishes' => $dishes,
            'recipeCount' => $recipeCount,
        ]);
    }
}