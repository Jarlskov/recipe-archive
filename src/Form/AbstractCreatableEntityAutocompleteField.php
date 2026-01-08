<?php

declare(strict_types=1);

namespace App\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Base class for Autocomplete fields that support creating new entities on the fly.
 * Uses TextType as parent to bypass EntityType strict choice validation.
 */
abstract class AbstractCreatableEntityAutocompleteField extends AbstractType
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param Security $security
     */
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly Security $security
    ) {
    }

    /**
     * @return string
     */
    abstract protected function getEntityClass(): string;

    /**
     * @return string
     */
    abstract protected function getRouteAlias(): string;

    /**
     * @param string $label
     * @return object
     */
    abstract protected function createEntity(string $label): object;

    /**
     * @param FormBuilderInterface $builder
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($entities) use ($options): string {
                if ($options['multiple']) {
                    if ($entities instanceof Collection) {
                        return implode(',', $entities->map(fn($e) => $e->getId())->toArray());
                    }
                    return '';
                }
                return $entities && method_exists($entities, 'getId') ? (string) $entities->getId() : '';
            },
            function (mixed $ids) use ($options): mixed {
                if ($options['multiple']) {
                    $collection = new ArrayCollection();
                    if (empty($ids)) {
                        return $collection;
                    }

                    $values = is_string($ids) ? explode(',', $ids) : (is_array($ids) ? $ids : []);
                    foreach ($values as $value) {
                        $entity = $this->resolveEntity($value, $options);
                        if ($entity) {
                            $collection->add($entity);
                        }
                    }
                    return $collection;
                }

                return $ids ? $this->resolveEntity($ids, $options) : null;
            }
        ));
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $options
     * @return object|null
     */
    private function resolveEntity(mixed $value, array $options): ?object
    {
        if (is_array($value)) {
            $value = $value['value'] ?? reset($value);
        }

        if (!is_scalar($value) || empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return $this->entityManager->getRepository($this->getEntityClass())->find($value);
        }

        if ($options['create'] ?? true) {
            $repository = $this->entityManager->getRepository($this->getEntityClass());
            $entity = $repository->findOneBy(['name' => $value]);
            
            if (!$entity) {
                $entity = $this->createEntity((string) $value);
            }
            
            return $entity;
        }

        return null;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array<string, mixed> $options
     * @return void
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr']['data-controller'] = 'symfony--ux-autocomplete--autocomplete';
        $view->vars['attr']['data-symfony--ux-autocomplete--autocomplete-url-value'] = $this->urlGenerator->generate('ux_entity_autocomplete', [
            'alias' => $this->getRouteAlias(),
        ]);
        $view->vars['attr']['data-symfony--ux-autocomplete--autocomplete-min-characters-value'] = 1;

        $initialOptions = [];
        $selectedItems = [];
        $data = $form->getData();
        $entityClass = $this->getEntityClass();

        if ($options['multiple']) {
            if ($data instanceof Collection) {
                foreach ($data as $entity) {
                    $this->addInitialOption($initialOptions, $selectedItems, $entity, $entityClass);
                }
            }
        } elseif ($data) {
            $this->addInitialOption($initialOptions, $selectedItems, $data, $entityClass);
        }

        $tomSelectOptions = [
            'create' => $options['create'],
            'createOnBlur' => $options['create'],
            'plugins' => ['remove_button'],
            'options' => $initialOptions,
            'items' => $selectedItems,
            'valueField' => 'value',
            'labelField' => 'text',
            'searchField' => 'text',
            'maxItems' => $options['multiple'] ? null : 1,
        ];

        $view->vars['attr']['data-symfony--ux-autocomplete--autocomplete-tom-select-options-value'] = json_encode($tomSelectOptions, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array<string, mixed> $options
     * @return void
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        // Ensure no arrays are passed to HTML attributes, which causes "Array to string conversion" errors
        if (isset($view->vars['attr'])) {
            foreach ($view->vars['attr'] as $key => $value) {
                if (is_array($value)) {
                    unset($view->vars['attr'][$key]);
                }
            }
        }
        
        // Also check variables directly in the view
        foreach (['extra_options', 'tom_select_options'] as $key) {
            if (isset($view->vars[$key]) && is_array($view->vars[$key])) {
                unset($view->vars[$key]);
            }
        }
    }

    /**
     * @param array<int, array<string, string>> $options
     * @param array<int, string> $items
     * @param mixed $entity
     * @param string $entityClass
     * @return void
     */
    private function addInitialOption(array &$options, array &$items, mixed $entity, string $entityClass): void
    {
        if ($entity instanceof $entityClass && method_exists($entity, 'getId') && $entity->getId()) {
            $options[] = [
                'value' => (string) $entity->getId(),
                'text' => (string) $entity->getName(),
            ];
            $items[] = (string) $entity->getId();
        }
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => $this->getEntityClass(),
            'placeholder' => 'Search...',
            'choice_label' => 'name',
            'multiple' => true,
            'security' => 'ROLE_USER',
            'allow_extra_fields' => true,
            'searchable_fields' => ['name'],
            'create' => true,
        ]);

        $resolver->setDefined(['extra_options', 'tom_select_options']);
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return TextType::class;
    }
}