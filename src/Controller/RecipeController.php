<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\Recipe;
use App\Entity\User;
use App\Form\RecipeType;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage Recipes.
 */
#[Route('/recipe')]
#[IsGranted('ROLE_USER')]
class RecipeController extends AbstractController
{
    /**
     * Renders a form to create a new Recipe and handles the submission.
     * Optionally pre-selects a Dish if 'dish_id' is provided.
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param DishRepository $dishRepository
     * @return Response
     */
    #[Route('/new', name: 'app_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, DishRepository $dishRepository): Response
    {
        $recipe = new Recipe();
        
        // Check for pre-selected dish
        $dishId = $request->query->get('dish_id');
        if ($dishId) {
            $dish = $dishRepository->find($dishId);
            // Verify ownership
            if ($dish && $dish->getUser() === $this->getUser()) {
                $recipe->setDish($dish);
            }
        }

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $recipe->setUser($user);

            // If the user selected a dish in the form, ensure they own it
            // (Though the UI should filter it, backend validation is key)
            if ($recipe->getDish() && $recipe->getDish()->getUser() !== $user) {
                $this->addFlash('error', 'You cannot assign a recipe to a dish you do not own.');
                return $this->render('recipe/new.html.twig', [
                    'recipe' => $recipe,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($recipe);
            $entityManager->flush();

            $this->addFlash('success', 'Recipe created successfully!');

            if ($recipe->getDish()) {
                return $this->redirectToRoute('app_dish_show', ['id' => $recipe->getDish()->getId()]);
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('recipe/new.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }
}
