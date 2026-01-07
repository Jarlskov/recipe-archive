<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Dish;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * DishVoter determines if a user has permission to view or edit a specific Dish.
 */
final class DishVoter extends Voter
{
    public const EDIT = 'DISH_EDIT';
    public const VIEW = 'DISH_VIEW';
    public const DELETE = 'DISH_DELETE';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Dish;
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

        /** @var Dish $dish */
        $dish = $subject;

        return match ($attribute) {
            self::EDIT, self::VIEW, self::DELETE => $this->canAccess($dish, $user),
            default => false,
        };
    }

    /**
     * @param Dish $dish
     * @param UserInterface $user
     * @return bool
     */
    private function canAccess(Dish $dish, UserInterface $user): bool
    {
        // Users can only view/edit their own dishes
        return $dish->getUser() === $user;
    }
}
