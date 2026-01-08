<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dish;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DishControllerTest extends WebTestCase
{
    public function testCreateDishSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('dish-test@example.com');
        $user->setPassword('password');
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/dish/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Create Dish')->form([
            'dish[name]' => 'Lasagna',
        ]);
        
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        
        $this->assertSelectorTextContains('h1', 'Lasagna');
        $this->assertSelectorTextContains('.bg-green-50', 'Dish created successfully!');
    }

    public function testCannotViewOthersDish(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user1 = new User();
        $user1->setEmail('u1@example.com');
        $user1->setPassword('password');
        $entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('u2@example.com');
        $user2->setPassword('password');
        $entityManager->persist($user2);

        $dish = new Dish();
        $dish->setName('User 1 Dish');
        $dish->setUser($user1);
        $entityManager->persist($dish);
        
        $entityManager->flush();

        $client->loginUser($user2);

        $client->request('GET', sprintf('/dish/%d', $dish->getId()));

        $this->assertResponseStatusCodeSame(403);
    }
}
