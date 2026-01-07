<?php

declare(strict_types=1);

namespace App\Controller;

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
        
        $dishId = $request->query->get('dish_id');
        if ($dishId) {
            $dish = $dishRepository->find($dishId);
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

            // Backend validation: Ensure the assigned dish belongs to the user
            if ($recipe->getDish() && $recipe->getDish()->getUser() !== $user) {
                $this->addFlash('error', 'Invalid dish selection.');
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

    /**
     * Renders a form to edit an existing Recipe and handles the submission.
     *
     * @param Request $request
     * @param Recipe $recipe
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_recipe_edit', methods: ['GET', 'POST'])]
    #[IsGranted('RECIPE_EDIT', subject: 'recipe')]
    public function edit(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Backend validation: Ensure the assigned dish belongs to the user
            if ($recipe->getDish() && $recipe->getDish()->getUser() !== $this->getUser()) {
                $this->addFlash('error', 'Invalid dish selection.');
                return $this->render('recipe/edit.html.twig', [
                    'recipe' => $recipe,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Recipe updated successfully!');

            if ($recipe->getDish()) {
                return $this->redirectToRoute('app_dish_show', ['id' => $recipe->getDish()->getId()]);
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form,
        ]);
    }

    /**
     * Deletes a Recipe.
     *
     * @param Request $request
     * @param Recipe $recipe
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_recipe_delete', methods: ['POST'])]
    #[IsGranted('RECIPE_DELETE', subject: 'recipe')]
    public function delete(Request $request, Recipe $recipe, EntityManagerInterface $entityManager): Response
    {
        $dishId = $recipe->getDish()?->getId();

        if ($this->isCsrfTokenValid('delete'.$recipe->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($recipe);
            $entityManager->flush();

            $this->addFlash('success', 'Recipe deleted successfully!');
        }

        if ($dishId) {
            return $this->redirectToRoute('app_dish_show', ['id' => $dishId]);
        }

        return $this->redirectToRoute('app_dashboard');
    }
}