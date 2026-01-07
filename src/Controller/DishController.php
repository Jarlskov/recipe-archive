<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\User;
use App\Form\DishType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to manage Dishes.
 */
#[Route('/dish')]
#[IsGranted('ROLE_USER')]
class DishController extends AbstractController
{
    /**
     * Renders a form to create a new Dish and handles the submission.
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/new', name: 'app_dish_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dish = new Dish();
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $dish->setUser($user);

            $entityManager->persist($dish);
            $entityManager->flush();

            $this->addFlash('success', 'Dish created successfully!');

            return $this->redirectToRoute('app_dish_show', ['id' => $dish->getId()]);
        }

        return $this->render('dish/new.html.twig', [
            'dish' => $dish,
            'form' => $form,
        ]);
    }

    /**
     * Displays a single Dish and its recipes.
     *
     * @param Dish $dish
     * @return Response
     */
    #[Route('/{id}', name: 'app_dish_show', methods: ['GET'])]
    #[IsGranted('DISH_VIEW', subject: 'dish')]
    public function show(Dish $dish): Response
    {
        return $this->render('dish/show.html.twig', [
            'dish' => $dish,
        ]);
    }

    /**
     * Renders a form to edit an existing Dish and handles the submission.
     *
     * @param Request $request
     * @param Dish $dish
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_dish_edit', methods: ['GET', 'POST'])]
    #[IsGranted('DISH_EDIT', subject: 'dish')]
    public function edit(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Dish updated successfully!');

            return $this->redirectToRoute('app_dish_show', ['id' => $dish->getId()]);
        }

        return $this->render('dish/edit.html.twig', [
            'dish' => $dish,
            'form' => $form,
        ]);
    }

    /**
     * Deletes a Dish and all its recipes (via orphanRemoval).
     *
     * @param Request $request
     * @param Dish $dish
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_dish_delete', methods: ['POST'])]
    #[IsGranted('DISH_DELETE', subject: 'dish')]
    public function delete(Request $request, Dish $dish, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dish->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($dish);
            $entityManager->flush();

            $this->addFlash('success', 'Dish deleted successfully!');
        }

        return $this->redirectToRoute('app_dashboard');
    }
}
