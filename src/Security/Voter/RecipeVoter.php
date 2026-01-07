<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Recipe;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * RecipeVoter determines if a user has permission to view, edit, or delete a specific Recipe.
 */
final class RecipeVoter extends Voter
{
    public const EDIT = 'RECIPE_EDIT';
    public const VIEW = 'RECIPE_VIEW';
    public const DELETE = 'RECIPE_DELETE';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Recipe;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @param Vote|null $vote
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Recipe $recipe */
        $recipe = $subject;

        return match ($attribute) {
            self::EDIT, self::VIEW, self::DELETE => $this->canAccess($recipe, $user),
            default => false,
        };
    }

    /**
     * @param Recipe $recipe
     * @param UserInterface $user
     * @return bool
     */
    private function canAccess(Recipe $recipe, UserInterface $user): bool
    {
        // Users can only view/edit/delete their own recipes
        return $recipe->getUser() === $user;
    }
}