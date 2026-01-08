<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\ResetPasswordRequest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResetPasswordControllerTest extends WebTestCase
{
    public function testRequestPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Reset your password');
    }

    public function testSubmitRequestFlow(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('reset-flow@example.com');
        $user->setPassword('old');
        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/reset-password');
        $form = $crawler->filter('button[type="submit"]')->form([
            'reset_password_request_form[email]' => 'reset-flow@example.com',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/reset-password/check-email');
        
        // Verify DB entry
        $request = $entityManager->getRepository(ResetPasswordRequest::class)->findOneBy([]);
        $this->assertNotNull($request);
    }
}