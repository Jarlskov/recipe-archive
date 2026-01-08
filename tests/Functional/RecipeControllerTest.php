<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\Recipe;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecipeControllerTest extends WebTestCase
{
    public function testNewRecipePageIsProtected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/recipe/new');

        $this->assertResponseRedirects('/login');
    }

    public function testCreateRecipeSuccess(): void
    {
        $client = static::createClient();
        
        // 1. Create and persist a user
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password'); // Password hashing not strictly needed for loginUser() in tests if not using actual login form
        $user->setIsVerified(true);
        
        $entityManager->persist($user);
        $entityManager->flush();

        // 2. Log in
        $client->loginUser($user);

        // 3. Go to new recipe page
        $crawler = $client->request('GET', '/recipe/new');
        $this->assertResponseIsSuccessful();

        // 4. Submit form
        $form = $crawler->selectButton('Save Recipe')->form([
            'recipe[title]' => 'Spaghetti Carbonara',
            'recipe[reference]' => 'https://example.com/carbonara',
            'recipe[author]' => 'Giallo Zafferano',
        ]);
        
        $client->submit($form);

        // 5. Assert redirect and success message
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertSelectorTextContains('.bg-green-50', 'Recipe created successfully!');

        // 6. Verify in database
        $recipe = $entityManager->getRepository(Recipe::class)->findOneBy(['title' => 'Spaghetti Carbonara']);
        $this->assertNotNull($recipe);
        $this->assertEquals('test@example.com', $recipe->getUser()->getEmail());
    }

    public function testCannotEditOthersRecipe(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // 1. Create two users
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user1->setPassword('password');
        $entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('user2@example.com');
        $user2->setPassword('password');
        $entityManager->persist($user2);

        // 2. Create a recipe for user 1
        $recipe = new Recipe();
        $recipe->setTitle('User 1 Recipe');
        $recipe->setReference('ref');
        $recipe->setAuthor('author');
        $recipe->setUser($user1);
        $entityManager->persist($recipe);
        
        $entityManager->flush();

        // 3. Login as user 2
        $client->loginUser($user2);

        // 4. Try to access edit page of user 1's recipe
        $client->request('GET', sprintf('/recipe/%d/edit', $recipe->getId()));

        // 5. Should be forbidden (Voter should catch this)
        $this->assertResponseStatusCodeSame(403);
    }
}
