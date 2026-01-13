<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SettingsControllerTest extends WebTestCase
{
    private function createUser(string $email, bool $verified = false): User
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setIsVerified($verified);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function testAccessDeniedForAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/settings');
        $this->assertResponseRedirects('/login');
    }

    public function testSettingsPageUnverified(): void
    {
        $client = static::createClient();
        $user = $this->createUser('unverified@example.com', false);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/settings');
        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorTextContains('dd', 'unverified@example.com');
        $this->assertSelectorTextContains('dd span', 'Not Verified');
        $this->assertSelectorExists('form[action="/settings/resend-verification"]');
    }

    public function testResendVerification(): void
    {
        $client = static::createClient();
        $user = $this->createUser('resend@example.com', false);
        $client->loginUser($user);

        $client->request('GET', '/settings');
        $client->submitForm('Resend Verification Email');

        $this->assertResponseRedirects('/settings');
        
        // Assert email sent (before following redirect)
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHeaderSame($email, 'To', 'resend@example.com');

        $client->followRedirect();
        
        $this->assertSelectorTextContains('.bg-green-50', 'Verification email sent');
    }

    public function testSettingsPageVerified(): void
    {
        $client = static::createClient();
        $user = $this->createUser('verified@example.com', true);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/settings');
        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorTextContains('dd', 'verified@example.com');
        $this->assertSelectorTextContains('dd span', 'Verified');
        $this->assertSelectorNotExists('form[action="/settings/resend-verification"]');
    }
}
