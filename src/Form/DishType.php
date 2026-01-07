<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Dish;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Form type for creating or editing a Dish.
 */
class DishType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Dish Name',
                'attr' => [
                    'placeholder' => 'e.g., Spaghetti Carbonara',
                    'class' => 'appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm'
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter a name for the dish'),
                    new Length(
                        min: 3,
                        max: 255,
                        minMessage: 'The name must be at least {{ limit }} characters long',
                        maxMessage: 'The name cannot be longer than {{ limit }} characters'
                    ),
                ],
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
            'data_class' => Dish::class,
        ]);
    }
}