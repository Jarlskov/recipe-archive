<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecipeMetadataControllerTest extends WebTestCase
{
    public function testAccessDeniedForAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/recipe/metadata');
        
        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testAccessGrantedForUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('metadata-test@example.com');
        $user->setPassword('password');
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        // Request without URL param should return 400, proving access is granted but input is invalid
        $client->request('GET', '/recipe/metadata');
        $this->assertResponseStatusCodeSame(400);
    }
}
