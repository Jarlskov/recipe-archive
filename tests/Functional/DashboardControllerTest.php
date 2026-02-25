<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\Tag;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testDashboardDataIsolation(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // 1. Create two users
        $user1 = new User();
        $user1->setEmail('me@example.com');
        $user1->setPassword('pass');
        $entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('other@example.com');
        $user2->setPassword('pass');
        $entityManager->persist($user2);

        // 2. Create dishes and recipes for both
        $dish1 = (new Dish())->setName('My Dish')->setUser($user1);
        $entityManager->persist($dish1);

        $dish2 = (new Dish())->setName('Other Dish')->setUser($user2);
        $entityManager->persist($dish2);

        $recipe1 = (new Recipe())->setTitle('My Recipe')->setUser($user1)->setReference('ref')->setAuthor('author');
        $entityManager->persist($recipe1);

        $recipe2 = (new Recipe())->setTitle('Other Recipe')->setUser($user2)->setReference('ref')->setAuthor('author');
        $entityManager->persist($recipe2);

        $entityManager->flush();

        // 3. Login as User 1
        $client->loginUser($user1);
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        
        // 4. Verify only User 1 data is visible
        $this->assertSelectorTextContains('body', 'My Dish');
        $this->assertSelectorTextContains('body', 'My Recipe');
        $this->assertSelectorTextNotContains('body', 'Other Dish');
        $this->assertSelectorTextNotContains('body', 'Other Recipe');
    }

    public function testAnonymousUserIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }

    public function testTextSearchFiltersDishesAndRecipes(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('search@example.com');
        $user->setPassword('pass');
        $entityManager->persist($user);

        $matchingDish = (new Dish())->setName('Pasta Carbonara')->setUser($user);
        $entityManager->persist($matchingDish);

        $otherDish = (new Dish())->setName('Chicken Soup')->setUser($user);
        $entityManager->persist($otherDish);

        $matchingRecipe = (new Recipe())->setTitle('Carbonara Classic')->setUser($user)->setReference('ref')->setAuthor('Mario');
        $entityManager->persist($matchingRecipe);

        $otherRecipe = (new Recipe())->setTitle('Tomato Soup')->setUser($user)->setReference('ref')->setAuthor('Chef');
        $entityManager->persist($otherRecipe);

        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/', ['q' => 'Carbonara']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Pasta Carbonara');
        $this->assertSelectorTextNotContains('body', 'Chicken Soup');
        $this->assertSelectorTextContains('body', 'Carbonara Classic');
        $this->assertSelectorTextNotContains('body', 'Tomato Soup');
    }

    public function testTextSearchMatchesOnAuthor(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('author-search@example.com');
        $user->setPassword('pass');
        $entityManager->persist($user);

        $matchingRecipe = (new Recipe())->setTitle('Some Dish')->setUser($user)->setReference('ref')->setAuthor('Marcella Hazan');
        $entityManager->persist($matchingRecipe);

        $otherRecipe = (new Recipe())->setTitle('Other Dish')->setUser($user)->setReference('ref')->setAuthor('Gordon Ramsay');
        $entityManager->persist($otherRecipe);

        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/', ['q' => 'Marcella']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Some Dish');
        $this->assertSelectorTextNotContains('body', 'Other Dish');
    }

    public function testEmptySearchShowsAllContent(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('empty-search@example.com');
        $user->setPassword('pass');
        $entityManager->persist($user);

        $dish = (new Dish())->setName('My Dish')->setUser($user);
        $entityManager->persist($dish);

        $recipe = (new Recipe())->setTitle('My Recipe')->setUser($user)->setReference('ref')->setAuthor('author');
        $entityManager->persist($recipe);

        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'My Dish');
        $this->assertSelectorTextContains('body', 'My Recipe');
    }

    public function testMultipleTagFiltering(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('tags@example.com');
        $user->setPassword('pass');
        $entityManager->persist($user);

        $tagA = (new Tag())->setName('TagA');
        $tagB = (new Tag())->setName('TagB');
        $entityManager->persist($tagA);
        $entityManager->persist($tagB);

        // Recipe with BOTH tags
        $recipeBoth = (new Recipe())->setTitle('Recipe Both')->setUser($user)->setReference('ref')->setAuthor('author');
        $recipeBoth->addTag($tagA);
        $recipeBoth->addTag($tagB);
        $entityManager->persist($recipeBoth);

        // Recipe with only ONE tag
        $recipeA = (new Recipe())->setTitle('Recipe A')->setUser($user)->setReference('ref')->setAuthor('author');
        $recipeA->addTag($tagA);
        $entityManager->persist($recipeA);

        $entityManager->flush();

        $client->loginUser($user);

        // 1. Filter by both tags via query params (how the form submits)
        // SearchFilterType has empty block prefix, so fields are at top level
        $client->request('GET', '/', [
            'tags' => sprintf('%d,%d', $tagA->getId(), $tagB->getId())
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#unassigned-recipes', 'Recipe Both');
        $this->assertSelectorTextNotContains('#unassigned-recipes', 'Recipe A');
    }

    public function testIngredientFiltering(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('ingredients@example.com');
        $user->setPassword('pass');
        $entityManager->persist($user);

        $ingA = (new Ingredient())->setName('Garlic');
        $ingB = (new Ingredient())->setName('Tomato');
        $entityManager->persist($ingA);
        $entityManager->persist($ingB);

        $recipeBoth = (new Recipe())->setTitle('Garlic Tomato Pasta')->setUser($user)->setReference('ref')->setAuthor('author');
        $recipeBoth->addIngredient($ingA);
        $recipeBoth->addIngredient($ingB);
        $entityManager->persist($recipeBoth);

        $recipeOne = (new Recipe())->setTitle('Garlic Bread')->setUser($user)->setReference('ref')->setAuthor('author');
        $recipeOne->addIngredient($ingA);
        $entityManager->persist($recipeOne);

        $entityManager->flush();

        $client->loginUser($user);

        // Filter by both ingredients — only recipe with BOTH should appear
        $client->request('GET', '/', [
            'ingredients' => sprintf('%d,%d', $ingA->getId(), $ingB->getId())
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#unassigned-recipes', 'Garlic Tomato Pasta');
        $this->assertSelectorTextNotContains('#unassigned-recipes', 'Garlic Bread');
    }

    public function testSearchNoResultsShowsEmptyState(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('no-results@example.com');
        $user->setPassword('pass');
        $entityManager->persist($user);

        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/', ['q' => 'xyznonexistent']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'No results found');
    }
}
