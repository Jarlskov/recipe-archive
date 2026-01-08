<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationFlow(): void
    {
        $client = static::createClient();
        
        // 1. Go to register page
        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        // 2. Submit registration form
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword]' => 'SecurePassword123!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $client->submit($form);

        // 3. Assert redirect to login
        $this->assertResponseRedirects('/login');

        // 4. Verify user exists in database
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'newuser@example.com']);
        
        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());

        // 5. Verify email was sent
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHeaderSame($email, 'To', 'newuser@example.com');
        $this->assertEmailHeaderSame($email, 'Subject', 'Please Confirm your Email');
    }
}
