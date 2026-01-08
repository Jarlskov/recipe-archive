<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('login-test@example.com');
        $user->setPassword($hasher->hashPassword($user, 'TestPassword123!'));
        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'login-test@example.com',
            '_password' => 'TestPassword123!',
        ]);
        
        $client->submit($form);

        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertSelectorTextContains('nav', 'Recipe Archive');
    }

    public function testLoginFailure(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'nonexistent@example.com',
            '_password' => 'wrong-password',
        ]);
        
        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.bg-red-50'); // Error flash message
    }
}
