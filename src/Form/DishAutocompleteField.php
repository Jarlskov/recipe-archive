<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Dish;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

/**
 * Autocomplete field for Dish selection.
 * Extends the abstract class but disables creation.
 */
#[AsEntityAutocompleteField(alias: 'dish_autocomplete_field')]
class DishAutocompleteField extends AbstractCreatableEntityAutocompleteField
{
    /**
     * @return string
     */
    protected function getEntityClass(): string
    {
        return Dish::class;
    }

    /**
     * @return string
     */
    protected function getRouteAlias(): string
    {
        return 'dish_autocomplete_field';
    }

    /**
     * @param string $label
     * @return Dish
     */
    protected function createEntity(string $label): Dish
    {
        return new Dish();
    }

    /**
     * @return bool
     */
    protected function canCreate(): bool
    {
        return false;
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        
        $resolver->setDefaults([
            'multiple' => false,
            'placeholder' => 'Select a Dish (Optional)',
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('entity')
                    ->where('entity.user = :user')
                    ->setParameter('user', $this->security->getUser())
                    ->orderBy('entity.name', 'ASC');
            },
        ]);
    }
}