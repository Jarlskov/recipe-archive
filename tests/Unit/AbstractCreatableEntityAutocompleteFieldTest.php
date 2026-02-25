<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Tag;
use App\Entity\User;
use App\Form\TagAutocompleteField;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AbstractCreatableEntityAutocompleteFieldTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturn($this->repository);
    }

    public function testTransformerConvertsEntitiesToIds(): void
    {
        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        // Capture the transformer
        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true]);

        $this->assertNotNull($capturedTransformer);

        // Test the transform direction (entity collection -> CSV string)
        $tag1 = $this->createMock(Tag::class);
        $tag1->method('getId')->willReturn(1);

        $tag2 = $this->createMock(Tag::class);
        $tag2->method('getId')->willReturn(2);

        $collection = new ArrayCollection([$tag1, $tag2]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $transformMethod = $reflection->getMethod('transform');
        $transformMethod->setAccessible(true);

        $result = $transformMethod->invoke($capturedTransformer, $collection);

        $this->assertEquals('1,2', $result);
    }

    public function testTransformerConvertsIdsToEntities(): void
    {
        $tag1 = $this->createMock(Tag::class);
        $tag1->method('getId')->willReturn(1);

        $tag2 = $this->createMock(Tag::class);
        $tag2->method('getId')->willReturn(2);

        $this->repository
            ->method('find')
            ->willReturnCallback(function ($id) use ($tag1, $tag2) {
                return match ((int)$id) {
                    1 => $tag1,
                    2 => $tag2,
                    default => null,
                };
            });

        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true]);

        // Test reverse transform (CSV string -> entity collection)
        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, '1,2');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testTransformerCreatesNewEntityWhenNotFound(): void
    {
        $this->repository
            ->method('find')
            ->willReturn(null);

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true, 'create' => true]);

        // Test reverse transform with string (new entity name)
        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, 'NewTag');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Tag::class, $result->first());
        $this->assertEquals('NewTag', $result->first()->getName());
    }

    public function testTransformerReusesExistingEntityByName(): void
    {
        $existingTag = $this->createMock(Tag::class);
        $existingTag->method('getName')->willReturn('ExistingTag');
        $existingTag->method('getId')->willReturn(99);

        $this->repository
            ->method('find')
            ->willReturn(null);

        $this->repository
            ->method('findOneBy')
            ->with(['name' => 'ExistingTag'])
            ->willReturn($existingTag);

        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true, 'create' => true]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, 'ExistingTag');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame($existingTag, $result->first());
    }

    public function testTransformerHandlesEmptyInput(): void
    {
        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, '');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(0, $result);
    }

    public function testTransformerSingleModeConvertsEntityToId(): void
    {
        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => false]);

        $tag = $this->createMock(Tag::class);
        $tag->method('getId')->willReturn(42);

        $reflection = new \ReflectionClass($capturedTransformer);
        $transformMethod = $reflection->getMethod('transform');
        $transformMethod->setAccessible(true);

        $result = $transformMethod->invoke($capturedTransformer, $tag);

        $this->assertEquals('42', $result);
    }

    public function testTransformerSingleModeConvertsIdToEntity(): void
    {
        $tag = $this->createMock(Tag::class);
        $tag->method('getId')->willReturn(42);

        $this->repository
            ->method('find')
            ->with(42)
            ->willReturn($tag);

        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => false]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, '42');

        $this->assertSame($tag, $result);
    }

    public function testTransformerSingleModeHandlesNull(): void
    {
        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => false]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, null);

        $this->assertNull($result);
    }

    public function testTransformerDoesNotCreateWhenCreateIsFalse(): void
    {
        $this->repository
            ->method('find')
            ->willReturn(null);

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true, 'create' => false]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        $result = $reverseMethod->invoke($capturedTransformer, 'NewTag');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(0, $result);
    }

    public function testTransformerHandlesMixedNumericAndStringIds(): void
    {
        $existingTag = $this->createMock(Tag::class);
        $existingTag->method('getId')->willReturn(1);
        $existingTag->method('getName')->willReturn('Existing');

        $this->repository
            ->method('find')
            ->with(1)
            ->willReturn($existingTag);

        $this->repository
            ->method('findOneBy')
            ->with(['name' => 'NewTag'])
            ->willReturn(null);

        $field = new TagAutocompleteField(
            $this->entityManager,
            $this->urlGenerator,
            $this->security
        );

        $builder = $this->createMock(FormBuilderInterface::class);

        $capturedTransformer = null;
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->willReturnCallback(function ($transformer) use (&$capturedTransformer, $builder) {
                $capturedTransformer = $transformer;
                return $builder;
            });

        $field->buildForm($builder, ['multiple' => true, 'create' => true]);

        $reflection = new \ReflectionClass($capturedTransformer);
        $reverseMethod = $reflection->getMethod('reverseTransform');
        $reverseMethod->setAccessible(true);

        // Mix of numeric ID and string name
        $result = $reverseMethod->invoke($capturedTransformer, '1,NewTag');

        $this->assertInstanceOf(ArrayCollection::class, $result);
        $this->assertCount(2, $result);
    }
}
