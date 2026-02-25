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

    public function testInvalidUrlReturns400(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('invalid-url-test@example.com');
        $user->setPassword('password');
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        // Test with invalid URL
        $client->request('GET', '/recipe/metadata', ['url' => 'not-a-url']);
        $this->assertResponseStatusCodeSame(400);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid URL', $data['error']);
    }

    public function testFetchingNonExistentUrlReturnsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('fetch-error-test@example.com');
        $user->setPassword('password');
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        // Try to fetch from a definitely non-existent domain
        $client->request('GET', '/recipe/metadata', ['url' => 'https://this-domain-definitely-does-not-exist-12345.com']);

        // Should return 500 with error message
        $this->assertResponseStatusCodeSame(500);
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Could not fetch metadata', $data['error']);
    }

}
