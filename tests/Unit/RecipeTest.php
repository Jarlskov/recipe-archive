<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\Dish;
use PHPUnit\Framework\TestCase;

class RecipeTest extends TestCase
{
    public function testRecipeInitialState(): void
    {
        $recipe = new Recipe();

        $this->assertNull($recipe->getId());
        $this->assertNull($recipe->getTitle());
        $this->assertNull($recipe->getReference());
        $this->assertNull($recipe->getAuthor());
        $this->assertInstanceOf(\DateTimeImmutable::class, $recipe->getCreatedAt());
        $this->assertCount(0, $recipe->getTags());
        $this->assertCount(0, $recipe->getIngredients());
        $this->assertNull($recipe->getDish());
        $this->assertNull($recipe->getUser());
    }

    public function testSettersAndGetters(): void
    {
        $recipe = new Recipe();
        $title = 'Test Recipe';
        $reference = 'https://example.com';
        $author = 'Test Author';
        $createdAt = new \DateTimeImmutable('2026-01-08');
        $user = new User();
        $dish = new Dish();

        $recipe->setTitle($title)
            ->setReference($reference)
            ->setAuthor($author)
            ->setCreatedAt($createdAt)
            ->setUser($user)
            ->setDish($dish);

        $this->assertEquals($title, $recipe->getTitle());
        $this->assertEquals($reference, $recipe->getReference());
        $this->assertEquals($author, $recipe->getAuthor());
        $this->assertEquals($createdAt, $recipe->getCreatedAt());
        $this->assertSame($user, $recipe->getUser());
        $this->assertSame($dish, $recipe->getDish());
    }

    public function testTagManagement(): void
    {
        $recipe = new Recipe();
        $tag = new Tag();

        $recipe->addTag($tag);
        $this->assertCount(1, $recipe->getTags());
        $this->assertTrue($recipe->getTags()->contains($tag));

        $recipe->removeTag($tag);
        $this->assertCount(0, $recipe->getTags());
        $this->assertFalse($recipe->getTags()->contains($tag));
    }

    public function testIngredientManagement(): void
    {
        $recipe = new Recipe();
        $ingredient = new Ingredient();

        $recipe->addIngredient($ingredient);
        $this->assertCount(1, $recipe->getIngredients());
        $this->assertTrue($recipe->getIngredients()->contains($ingredient));

        $recipe->removeIngredient($ingredient);
        $this->assertCount(0, $recipe->getIngredients());
        $this->assertFalse($recipe->getIngredients()->contains($ingredient));
    }
}
