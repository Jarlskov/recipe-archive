<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Ingredient;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

/**
 * Autocomplete field for Ingredient selection.
 */
#[AsEntityAutocompleteField(alias: 'ingredient_autocomplete_field')]
class IngredientAutocompleteField extends AbstractCreatableEntityAutocompleteField
{
    /**
     * @return string
     */
    protected function getEntityClass(): string
    {
        return Ingredient::class;
    }

    /**
     * @return string
     */
    protected function getRouteAlias(): string
    {
        return 'ingredient_autocomplete_field';
    }

    /**
     * @param string $label
     * @return Ingredient
     */
    protected function createEntity(string $label): Ingredient
    {
        return (new Ingredient())->setName($label);
    }
}