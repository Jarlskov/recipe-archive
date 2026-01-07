<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Tag;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

/**
 * Autocomplete field for Tag selection.
 */
#[AsEntityAutocompleteField(alias: 'tag_autocomplete_field')]
class TagAutocompleteField extends AbstractCreatableEntityAutocompleteField
{
    /**
     * @return string
     */
    protected function getEntityClass(): string
    {
        return Tag::class;
    }

    /**
     * @return string
     */
    protected function getRouteAlias(): string
    {
        return 'tag_autocomplete_field';
    }

    /**
     * @param string $label
     * @return Tag
     */
    protected function createEntity(string $label): Tag
    {
        return (new Tag())->setName($label);
    }
}