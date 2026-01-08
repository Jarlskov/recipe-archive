<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RecipeRepositoryTest extends KernelTestCase
{
    private RecipeRepository $repository;
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Recipe::class);
    }

    public function testFindStandaloneRecipesWithFilters(): void
    {
        // 1. Setup User
        $user = new User();
        $user->setEmail('repo-test@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        // 2. Setup Tags and Ingredients
        $tag1 = new Tag(); $tag1->setName('Tag 1'); $this->entityManager->persist($tag1);
        $tag2 = new Tag(); $tag2->setName('Tag 2'); $this->entityManager->persist($tag2);
        $ing1 = new Ingredient(); $ing1->setName('Ing 1'); $this->entityManager->persist($ing1);
        
        // 3. Create Recipes
        $recipe1 = new Recipe();
        $recipe1->setTitle('Recipe 1')->setUser($user)->addTag($tag1)->addIngredient($ing1)
            ->setReference('ref1')->setAuthor('author1');
        $this->entityManager->persist($recipe1);

        $recipe2 = new Recipe();
        $recipe2->setTitle('Recipe 2')->setUser($user)->addTag($tag1)->addTag($tag2)
            ->setReference('ref2')->setAuthor('author2');
        $this->entityManager->persist($recipe2);

        $recipe3 = new Recipe();
        $recipe3->setTitle('Recipe with Dish')->setUser($user)
            ->setReference('ref3')->setAuthor('author3'); // No dish set yet, will be standalone if dish is null
        $this->entityManager->persist($recipe3);

        $this->entityManager->flush();

        // 4. Test filtering by user
        $results = $this->repository->findStandaloneRecipes($user);
        $this->assertCount(3, $results);

        // 5. Test filtering by query
        $results = $this->repository->findStandaloneRecipes($user, 'Recipe 1');
        $this->assertCount(1, $results);
        $this->assertEquals('Recipe 1', $results[0]->getTitle());

        // 6. Test mandatory filter (AND logic) for Tags
        // Search for recipes having BOTH Tag 1 and Tag 2
        $results = $this->repository->findStandaloneRecipes($user, null, [$tag1, $tag2]);
        $this->assertCount(1, $results);
        $this->assertEquals('Recipe 2', $results[0]->getTitle());

        // 7. Test mandatory filter for Ingredients
        $results = $this->repository->findStandaloneRecipes($user, null, [], [$ing1]);
        $this->assertCount(1, $results);
        $this->assertEquals('Recipe 1', $results[0]->getTitle());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
