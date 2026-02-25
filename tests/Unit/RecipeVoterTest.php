<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Recipe;
use App\Entity\User;
use App\Security\Voter\RecipeVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class RecipeVoterTest extends TestCase
{
    private RecipeVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new RecipeVoter();
    }

    public function testSupportsRecipeEditAttribute(): void
    {
        $recipe = $this->createMock(Recipe::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, RecipeVoter::EDIT, $recipe);
        $this->assertTrue($result);
    }

    public function testSupportsRecipeViewAttribute(): void
    {
        $recipe = $this->createMock(Recipe::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, RecipeVoter::VIEW, $recipe);
        $this->assertTrue($result);
    }

    public function testSupportsRecipeDeleteAttribute(): void
    {
        $recipe = $this->createMock(Recipe::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, RecipeVoter::DELETE, $recipe);
        $this->assertTrue($result);
    }

    public function testDoesNotSupportInvalidAttribute(): void
    {
        $recipe = $this->createMock(Recipe::class);

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, 'INVALID_ATTRIBUTE', $recipe);
        $this->assertFalse($result);
    }

    public function testDoesNotSupportNonRecipeSubject(): void
    {
        $notARecipe = new \stdClass();

        $reflection = new \ReflectionClass($this->voter);
        $method = $reflection->getMethod('supports');
        $method->setAccessible(true);

        $result = $method->invoke($this->voter, RecipeVoter::EDIT, $notARecipe);
        $this->assertFalse($result);
    }

    public function testOwnerCanViewRecipe(): void
    {
        $user = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanEditRecipe(): void
    {
        $user = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanDeleteRecipe(): void
    {
        $user = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonOwnerCannotViewRecipe(): void
    {
        $owner = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonOwnerCannotEditRecipe(): void
    {
        $owner = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonOwnerCannotDeleteRecipe(): void
    {
        $owner = $this->createMock(User::class);
        $otherUser = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserCannotViewRecipe(): void
    {
        $owner = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserCannotEditRecipe(): void
    {
        $owner = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousUserCannotDeleteRecipe(): void
    {
        $owner = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $recipe, [RecipeVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $user = $this->createMock(User::class);

        $recipe = $this->createMock(Recipe::class);
        $recipe->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $recipe, ['UNSUPPORTED_ATTRIBUTE']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsOnUnsupportedSubject(): void
    {
        $user = $this->createMock(User::class);
        $notARecipe = new \stdClass();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $notARecipe, [RecipeVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
