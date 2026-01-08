<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Ingredient;
use App\Entity\Tag;
use App\Repository\IngredientRepository;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A form for searching and filtering the dashboard.
 * Uses manual Autocomplete attributes to avoid 'Array to string' rendering errors
 * while correctly resolving IDs to Names for the UI.
 */
class SearchFilterType extends AbstractType
{
    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod('GET')
            ->add('q', null, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Search dishes or recipes...',
                    'class' => 'block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm transition-all'
                ]
            ])
            ->add('tags', TextType::class, [
                'required' => false,
                'label' => 'Filter by Tags',
            ])
            ->add('ingredients', TextType::class, [
                'required' => false,
                'label' => 'Filter by Ingredients',
            ])
        ;

        $builder->get('tags')->addModelTransformer($this->createCollectionTransformer(Tag::class));
        $builder->get('ingredients')->addModelTransformer($this->createCollectionTransformer(Ingredient::class));
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array<string, mixed> $options
     * @return void
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Tag Autocomplete Attributes
        $tagData = $form->get('tags')->getData();
        $view->children['tags']->vars['attr'] = array_merge(
            $view->children['tags']->vars['attr'] ?? [],
            $this->getAutocompleteAttr('tag_autocomplete_field', $tagData, Tag::class)
        );

        // Ingredient Autocomplete Attributes
        $ingredientData = $form->get('ingredients')->getData();
        $view->children['ingredients']->vars['attr'] = array_merge(
            $view->children['ingredients']->vars['attr'] ?? [],
            $this->getAutocompleteAttr('ingredient_autocomplete_field', $ingredientData, Ingredient::class)
        );
    }

    /**
     * @param string $alias
     * @param mixed $data
     * @param string $class
     * @return array<string, string>
     */
    private function getAutocompleteAttr(string $alias, mixed $data, string $class): array
    {
        $initialOptions = [];
        $selectedItems = [];

        if ($data instanceof Collection) {
            foreach ($data as $entity) {
                if ($entity instanceof $class && method_exists($entity, 'getId')) {
                    $initialOptions[] = [
                        'value' => (string) $entity->getId(),
                        'text' => (string) $entity->getName(),
                    ];
                    $selectedItems[] = (string) $entity->getId();
                }
            }
        }

        return [
            'data-controller' => 'symfony--ux-autocomplete--autocomplete',
            'data-symfony--ux-autocomplete--autocomplete-url-value' => $this->urlGenerator->generate('ux_entity_autocomplete', ['alias' => $alias]),
            'data-symfony--ux-autocomplete--autocomplete-min-characters-value' => '1',
            'data-symfony--ux-autocomplete--autocomplete-tom-select-options-value' => json_encode([
                'create' => false,
                'plugins' => ['remove_button'],
                'valueField' => 'value',
                'labelField' => 'text',
                'searchField' => 'text',
                'options' => $initialOptions,
                'items' => $selectedItems,
            ]),
        ];
    }

    /**
     * @param string $class
     * @return CallbackTransformer
     */
    private function createCollectionTransformer(string $class): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($entities): string {
                if ($entities instanceof Collection) {
                    return implode(',', $entities->map(fn($e) => $e->getId())->toArray());
                }
                return '';
            },
            function (mixed $ids) use ($class): Collection {
                $collection = new ArrayCollection();
                if (empty($ids)) {
                    return $collection;
                }

                $values = is_string($ids) ? explode(',', $ids) : (is_array($ids) ? $ids : []);
                $repo = $this->entityManager->getRepository($class);

                foreach ($values as $value) {
                    if (is_numeric($value)) {
                        $entity = $repo->find($value);
                        if ($entity) {
                            $collection->add($entity);
                        }
                    }
                }
                return $collection;
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
