<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    public function testUniqueEmailConstraint(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $user1 = new User();
        $user1->setEmail('unique@example.com');
        $user1->setPassword('pass');
        $entityManager->persist($user1);
        $entityManager->flush();

        $user2 = new User();
        $user2->setEmail('unique@example.com');
        $user2->setPassword('pass');
        $entityManager->persist($user2);

        $this->expectException(UniqueConstraintViolationException::class);
        $entityManager->flush();
    }
}
