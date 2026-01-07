<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Recipe;
use App\Form\DishAutocompleteField;
use App\Form\IngredientAutocompleteField;
use App\Form\TagAutocompleteField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Form type for creating or editing a Recipe.
 */
class RecipeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Recipe Title',
                'attr' => [
                    'placeholder' => 'e.g., Mom\'s Spicy Carbonara',
                    'class' => 'appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter a title'),
                    new Length(max: 255),
                ],
            ])
            ->add('author', TextType::class, [
                'label' => 'Author / Source',
                'attr' => [
                    'placeholder' => 'e.g., Jamie Oliver',
                    'class' => 'appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter an author or source'),
                    new Length(max: 255),
                ],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Reference (URL or Book/Page)',
                'help' => 'Enter a full URL (https://...) or book details',
                'attr' => [
                    'placeholder' => 'e.g., https://cooking.com/recipe or "My Cookbook, Page 42"',
                    'class' => 'appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter a reference'),
                    new Length(max: 1024),
                ],
            ])
            ->add('dish', DishAutocompleteField::class, [
                'required' => false,
                'label' => 'Assign to Dish',
            ])
            ->add('tags', TagAutocompleteField::class, [
                'required' => false,
            ])
            ->add('ingredients', IngredientAutocompleteField::class, [
                'required' => false,
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}