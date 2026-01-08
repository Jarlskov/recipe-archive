<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Dish;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;

/**
 * Autocomplete field for Dish selection.
 * Limits choices to those owned by the current user.
 * Uses the standard ParentEntityAutocompleteType for maximum stability.
 */
#[AsEntityAutocompleteField(alias: 'dish_autocomplete_field')]
class DishAutocompleteField extends AbstractType
{
    /**
     * @param Security $security
     */
    public function __construct(
        private readonly Security $security
    ) {
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Dish::class,
            'placeholder' => 'Select a Dish (Optional)',
            'choice_label' => 'name',
            'multiple' => false,
            'security' => 'ROLE_USER',
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('entity')
                    ->where('entity.user = :user')
                    ->setParameter('user', $this->security->getUser())
                    ->orderBy('entity.name', 'ASC');
            },
        ]);

        // Define these to prevent 'option does not exist' errors if the bundle tries to pass them
        $resolver->setDefined(['extra_options', 'tom_select_options']);
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return ParentEntityAutocompleteType::class;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array<string, mixed> $options
     * @return void
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Ensure no arrays are passed to HTML attributes
        if (isset($view->vars['attr'])) {
            foreach ($view->vars['attr'] as $key => $value) {
                if (is_array($value)) {
                    unset($view->vars['attr'][$key]);
                }
            }
        }
    }
}
