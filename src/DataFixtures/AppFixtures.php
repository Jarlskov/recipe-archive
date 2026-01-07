<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Dish;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * AppFixtures seeds the database with initial data for development and testing.
 */
class AppFixtures extends Fixture
{
    /**
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // 1. Create a Test User
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        // 2. Create Tags
        $tags = [];
        for ($i = 0; $i < 10; $i++) {
            $tag = new Tag();
            $tag->setName($faker->unique()->word());
            $manager->persist($tag);
            $tags[] = $tag;
        }

        // 3. Create Ingredients
        $ingredients = [];
        for ($i = 0; $i < 20; $i++) {
            $ingredient = new Ingredient();
            $ingredient->setName($faker->unique()->word());
            $manager->persist($ingredient);
            $ingredients[] = $ingredient;
        }

        // 4. Create Dishes and Recipes
        for ($i = 0; $i < 5; $i++) {
            $dish = new Dish();
            $dish->setName($faker->sentence(3));
            $dish->setUser($user);
            $manager->persist($dish);

            // Create 2-3 recipes per dish
            for ($j = 0; $j < rand(2, 3); $j++) {
                $recipe = new Recipe();
                $recipe->setTitle($faker->sentence(4));
                $recipe->setAuthor($faker->name());
                $recipe->setUser($user);
                $recipe->setDish($dish);

                // Mix between URLs and Book References
                if ($faker->boolean(60)) {
                    $recipe->setReference($faker->url());
                } else {
                    $recipe->setReference('Book: ' . $faker->catchPhrase() . ', Page ' . $faker->numberBetween(1, 300));
                }

                // Add random tags
                for ($k = 0; $k < rand(1, 3); $k++) {
                    $recipe->addTag($tags[array_rand($tags)]);
                }

                // Add random ingredients
                for ($k = 0; $k < rand(2, 5); $k++) {
                    $recipe->addIngredient($ingredients[array_rand($ingredients)]);
                }

                $manager->persist($recipe);
            }
        }

        // 5. Create some standalone recipes
        for ($i = 0; $i < 3; $i++) {
            $recipe = new Recipe();
            $recipe->setTitle($faker->sentence(4) . ' (Standalone)');
            $recipe->setAuthor($faker->name());
            $recipe->setReference($faker->url());
            $recipe->setUser($user);

            // Add random tags and ingredients
            $recipe->addTag($tags[array_rand($tags)]);
            $recipe->addIngredient($ingredients[array_rand($ingredients)]);

            $manager->persist($recipe);
        }

        $manager->flush();
    }
}