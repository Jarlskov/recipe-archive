<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\DishRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DishRepositoryTest extends KernelTestCase
{
    private DishRepository $repository;
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Dish::class);
    }

    public function testFindDishesForUserBasic(): void
    {
        // 1. Setup User
        $user = new User();
        $user->setEmail('dish-repo-test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        // 2. Create Dishes
        $dish1 = new Dish();
        $dish1->setName('Carbonara')->setUser($user);
        $this->entityManager->persist($dish1);

        $dish2 = new Dish();
        $dish2->setName('Lasagna')->setUser($user);
        $this->entityManager->persist($dish2);

        $this->entityManager->flush();

        // 3. Test basic retrieval
        $results = $this->repository->findDishesForUser($user);
        $this->assertCount(2, $results);
    }

    public function testFindDishesForUserWithQuerySearch(): void
    {
        $user = new User();
        $user->setEmail('dish-query-test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $dish1 = new Dish();
        $dish1->setName('Spaghetti Carbonara')->setUser($user);
        $this->entityManager->persist($dish1);

        $dish2 = new Dish();
        $dish2->setName('Lasagna')->setUser($user);
        $this->entityManager->persist($dish2);

        $this->entityManager->flush();

        // Test query match by dish name
        $results = $this->repository->findDishesForUser($user, 'Carbonara');
        $this->assertCount(1, $results);
        $this->assertEquals('Spaghetti Carbonara', $results[0]->getName());

        // Test query match by partial name
        $results = $this->repository->findDishesForUser($user, 'Spag');
        $this->assertCount(1, $results);
        $this->assertEquals('Spaghetti Carbonara', $results[0]->getName());
    }

    public function testFindDishesForUserQuerySearchRecipeFields(): void
    {
        $user = new User();
        $user->setEmail('dish-recipe-search@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $dish = new Dish();
        $dish->setName('Pasta')->setUser($user);
        $this->entityManager->persist($dish);

        $recipe = new Recipe();
        $recipe->setTitle('Classic Roman Carbonara')
            ->setAuthor('Jamie Oliver')
            ->setReference('https://example.com')
            ->setUser($user)
            ->setDish($dish);
        $this->entityManager->persist($recipe);

        $this->entityManager->flush();

        // Search by recipe title
        $results = $this->repository->findDishesForUser($user, 'Roman');
        $this->assertCount(1, $results);
        $this->assertEquals('Pasta', $results[0]->getName());

        // Search by recipe author
        $results = $this->repository->findDishesForUser($user, 'Jamie');
        $this->assertCount(1, $results);
        $this->assertEquals('Pasta', $results[0]->getName());

        // Search that doesn't match should return nothing
        $results = $this->repository->findDishesForUser($user, 'NonExistent');
        $this->assertCount(0, $results);
    }

    public function testFindDishesForUserWithTagFilteringAndLogic(): void
    {
        $user = new User();
        $user->setEmail('dish-tag-test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $tag1 = new Tag();
        $tag1->setName('Quick');
        $this->entityManager->persist($tag1);

        $tag2 = new Tag();
        $tag2->setName('Vegetarian');
        $this->entityManager->persist($tag2);

        // Dish 1: Has recipe with BOTH tags
        $dish1 = new Dish();
        $dish1->setName('Pasta Primavera')->setUser($user);
        $this->entityManager->persist($dish1);

        $recipe1 = new Recipe();
        $recipe1->setTitle('Recipe 1')
            ->setUser($user)
            ->setReference('ref1')
            ->setAuthor('author1')
            ->setDish($dish1)
            ->addTag($tag1)
            ->addTag($tag2);
        $this->entityManager->persist($recipe1);

        // Dish 2: Has recipe with only ONE tag
        $dish2 = new Dish();
        $dish2->setName('Quick Chicken')->setUser($user);
        $this->entityManager->persist($dish2);

        $recipe2 = new Recipe();
        $recipe2->setTitle('Recipe 2')
            ->setUser($user)
            ->setReference('ref2')
            ->setAuthor('author2')
            ->setDish($dish2)
            ->addTag($tag1);
        $this->entityManager->persist($recipe2);

        // Dish 3: Has no recipes
        $dish3 = new Dish();
        $dish3->setName('Empty Dish')->setUser($user);
        $this->entityManager->persist($dish3);

        $this->entityManager->flush();

        // Filter by BOTH tags - should only return dish with recipe having both
        $results = $this->repository->findDishesForUser($user, null, [$tag1, $tag2]);
        $this->assertCount(1, $results);
        $this->assertEquals('Pasta Primavera', $results[0]->getName());

        // Filter by single tag - should return both dishes with that tag
        $results = $this->repository->findDishesForUser($user, null, [$tag1]);
        $this->assertCount(2, $results);
    }

    public function testFindDishesForUserWithIngredientFilteringAndLogic(): void
    {
        $user = new User();
        $user->setEmail('dish-ingredient-test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $ing1 = new Ingredient();
        $ing1->setName('Tomato');
        $this->entityManager->persist($ing1);

        $ing2 = new Ingredient();
        $ing2->setName('Basil');
        $this->entityManager->persist($ing2);

        $ing3 = new Ingredient();
        $ing3->setName('Mozzarella');
        $this->entityManager->persist($ing3);

        // Dish 1: Recipe with Tomato and Basil
        $dish1 = new Dish();
        $dish1->setName('Margherita Pizza')->setUser($user);
        $this->entityManager->persist($dish1);

        $recipe1 = new Recipe();
        $recipe1->setTitle('Recipe 1')
            ->setUser($user)
            ->setReference('ref1')
            ->setAuthor('author1')
            ->setDish($dish1)
            ->addIngredient($ing1)
            ->addIngredient($ing2);
        $this->entityManager->persist($recipe1);

        // Dish 2: Recipe with all three ingredients
        $dish2 = new Dish();
        $dish2->setName('Caprese Salad')->setUser($user);
        $this->entityManager->persist($dish2);

        $recipe2 = new Recipe();
        $recipe2->setTitle('Recipe 2')
            ->setUser($user)
            ->setReference('ref2')
            ->setAuthor('author2')
            ->setDish($dish2)
            ->addIngredient($ing1)
            ->addIngredient($ing2)
            ->addIngredient($ing3);
        $this->entityManager->persist($recipe2);

        $this->entityManager->flush();

        // Filter by Tomato and Basil - should return both dishes
        $results = $this->repository->findDishesForUser($user, null, [], [$ing1, $ing2]);
        $this->assertCount(2, $results);

        // Filter by all three - should only return Caprese Salad
        $results = $this->repository->findDishesForUser($user, null, [], [$ing1, $ing2, $ing3]);
        $this->assertCount(1, $results);
        $this->assertEquals('Caprese Salad', $results[0]->getName());

        // Filter by Mozzarella only
        $results = $this->repository->findDishesForUser($user, null, [], [$ing3]);
        $this->assertCount(1, $results);
        $this->assertEquals('Caprese Salad', $results[0]->getName());
    }

    public function testFindDishesForUserCombinedFilters(): void
    {
        $user = new User();
        $user->setEmail('dish-combined-test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $tag = new Tag();
        $tag->setName('Italian');
        $this->entityManager->persist($tag);

        $ingredient = new Ingredient();
        $ingredient->setName('Pasta');
        $this->entityManager->persist($ingredient);

        // Dish matching all criteria
        $dish1 = new Dish();
        $dish1->setName('Italian Carbonara')->setUser($user);
        $this->entityManager->persist($dish1);

        $recipe1 = new Recipe();
        $recipe1->setTitle('Classic Recipe')
            ->setUser($user)
            ->setReference('ref1')
            ->setAuthor('author1')
            ->setDish($dish1)
            ->addTag($tag)
            ->addIngredient($ingredient);
        $this->entityManager->persist($recipe1);

        // Dish with tag but no ingredient
        $dish2 = new Dish();
        $dish2->setName('Italian Pizza')->setUser($user);
        $this->entityManager->persist($dish2);

        $recipe2 = new Recipe();
        $recipe2->setTitle('Pizza Recipe')
            ->setUser($user)
            ->setReference('ref2')
            ->setAuthor('author2')
            ->setDish($dish2)
            ->addTag($tag);
        $this->entityManager->persist($recipe2);

        $this->entityManager->flush();

        // Combine query + tag + ingredient filters
        $results = $this->repository->findDishesForUser($user, 'Carbonara', [$tag], [$ingredient]);
        $this->assertCount(1, $results);
        $this->assertEquals('Italian Carbonara', $results[0]->getName());

        // Tag + ingredient without query
        $results = $this->repository->findDishesForUser($user, null, [$tag], [$ingredient]);
        $this->assertCount(1, $results);

        // Only tag filter - should return both
        $results = $this->repository->findDishesForUser($user, null, [$tag], []);
        $this->assertCount(2, $results);
    }

    public function testFindDishesForUserDataIsolation(): void
    {
        $user1 = new User();
        $user1->setEmail('user1-dishes@example.com');
        $user1->setPassword('password');
        $this->entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('user2-dishes@example.com');
        $user2->setPassword('password');
        $this->entityManager->persist($user2);

        $dish1 = new Dish();
        $dish1->setName('User 1 Dish')->setUser($user1);
        $this->entityManager->persist($dish1);

        $dish2 = new Dish();
        $dish2->setName('User 2 Dish')->setUser($user2);
        $this->entityManager->persist($dish2);

        $this->entityManager->flush();

        // User 1 should only see their dish
        $results = $this->repository->findDishesForUser($user1);
        $this->assertCount(1, $results);
        $this->assertEquals('User 1 Dish', $results[0]->getName());

        // User 2 should only see their dish
        $results = $this->repository->findDishesForUser($user2);
        $this->assertCount(1, $results);
        $this->assertEquals('User 2 Dish', $results[0]->getName());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
