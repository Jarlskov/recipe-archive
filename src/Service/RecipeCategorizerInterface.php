<?php

declare(strict_types=1);

namespace App\Service;

interface RecipeCategorizerInterface
{
    /**
     * Categorize a recipe by suggesting tags and ingredients.
     *
     * @param string $url URL of the recipe page
     * @param string[] $existingTags Names of tags the user already has
     * @param string[] $existingIngredients Names of ingredients the user already has
     * @return array{tags: string[], ingredients: string[]}
     */
    public function categorize(string $url, array $existingTags, array $existingIngredients): array;
}
