<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Dish;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dish')]
#[IsGranted('ROLE_USER')]
class DishController extends AbstractController
{
    #[Route('/{id}', name: 'app_dish_show', methods: ['GET'])]
    #[IsGranted('DISH_VIEW', subject: 'dish')]
    public function show(Dish $dish): Response
    {
        return $this->render('dish/show.html.twig', [
            'dish' => $dish,
        ]);
    }
}
