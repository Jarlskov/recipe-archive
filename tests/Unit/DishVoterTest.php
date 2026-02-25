<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Dish;
use App\Entity\User;
use App\Security\Voter\DishVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DishVoterTest extends TestCase
{
    private DishVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new DishVoter();
    }

    public function testSupportsDishEditAttribute(): void
    {
        $dish = $this->createMock(Dish::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, DishVoter::EDIT, $dish);
        $this->assertTrue($result);
    }

    public function testSupportsDishViewAttribute(): void
    {
        $dish = $this->createMock(Dish::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, DishVoter::VIEW, $dish);
        $this->assertTrue($result);
    }

    public function testSupportsDishDeleteAttribute(): void
    {
        $dish = $this->createMock(Dish::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, DishVoter::DELETE, $dish);
        $this->assertTrue($result);
    }

    public function testDoesNotSupportInvalidAttribute(): void
    {
        $dish = $this->createMock(Dish::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, 'INVALID_ATTRIBUTE', $dish);
        $this->assertFalse($result);
    }

    public function testDoesNotSupportNonDishSubject(): void
    {
        $notADish = new \stdClass();

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, DishVoter::EDIT, $notADish);
        $this->assertFalse($result);
    }

    public function testOwnerCanViewDish(): void
    {
        $user = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $dish, [DishVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanEditDish(): void
    {
        $user = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $dish, [DishVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanDeleteDish(): void
    {
        $user = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $dish, [DishVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonOwnerCannotViewDish(): void
    {
        $owner = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $dish, [DishVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonOwnerCannotEditDish(): void
    {
        $owner = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $dish, [DishVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonOwnerCannotDeleteDish(): void
    {
        $owner = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $dish, [DishVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserCannotViewDish(): void
    {
        $owner = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $dish, [DishVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserCannotEditDish(): void
    {
        $owner = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $dish, [DishVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserCannotDeleteDish(): void
    {
        $owner = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $dish, [DishVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $user = $this->createMock(User::class);

        $dish = $this->createMock(Dish::class);
        $dish->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $dish, ['UNSUPPORTED_ATTRIBUTE']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsOnUnsupportedSubject(): void
    {
        $user = $this->createMock(User::class);
        $notADish = new \stdClass();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $notADish, [DishVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
