<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Controller\RecipeMetadataController;
use App\Entity\Ingredient;
use App\Entity\Tag;
use App\Repository\IngredientRepository;
use App\Repository\TagRepository;
use App\Service\RecipeCategorizerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RecipeMetadataControllerTest extends TestCase
{
    private function makeTag(string $name): Tag
    {
        return (new Tag())->setName($name);
    }

    private function makeIngredient(string $name): Ingredient
    {
        return (new Ingredient())->setName($name);
    }

    private function makeHttpResponse(string $html): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($html);
        return $response;
    }

    public function testResponseIncludesTagsAndIngredientsFromCategorizer(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(
            $this->makeHttpResponse('<html><head><title>Pasta Carbonara</title></head><body></body></html>')
        );

        $tagRepo = $this->createStub(TagRepository::class);
        $tagRepo->method('findAll')->willReturn([]);

        $ingredientRepo = $this->createStub(IngredientRepository::class);
        $ingredientRepo->method('findAll')->willReturn([]);

        $categorizer = $this->createStub(RecipeCategorizerInterface::class);
        $categorizer->method('categorize')->willReturn(['tags' => ['Hurtig', 'Vegetar'], 'ingredients' => ['Pasta', 'Tomater']]);

        $controller = new RecipeMetadataController($httpClient, new NullLogger(), $categorizer, $tagRepo, $ingredientRepo);

        $request = Request::create('/recipe/metadata', 'GET', ['url' => 'https://example.com/recipe']);
        $response = $controller->__invoke($request);

        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Pasta Carbonara', $data['title']);
        $this->assertSame(['Hurtig', 'Vegetar'], $data['tags']);
        $this->assertSame(['Pasta', 'Tomater'], $data['ingredients']);
    }

    public function testCategorizerReceivesUrlAndExistingTagsAndIngredients(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(
            $this->makeHttpResponse('<html><head><title>Test</title></head><body></body></html>')
        );

        $tagRepo = $this->createStub(TagRepository::class);
        $tagRepo->method('findAll')->willReturn([$this->makeTag('Hurtig'), $this->makeTag('Vegetar')]);

        $ingredientRepo = $this->createStub(IngredientRepository::class);
        $ingredientRepo->method('findAll')->willReturn([$this->makeIngredient('Pasta'), $this->makeIngredient('Tomater')]);

        $categorizer = $this->createMock(RecipeCategorizerInterface::class);
        $categorizer->expects($this->once())
            ->method('categorize')
            ->with(
                'https://example.com/recipe',
                $this->containsEqual('Hurtig'),
                $this->containsEqual('Pasta'),
            )
            ->willReturn(['tags' => [], 'ingredients' => []]);

        $controller = new RecipeMetadataController($httpClient, new NullLogger(), $categorizer, $tagRepo, $ingredientRepo);

        $request = Request::create('/recipe/metadata', 'GET', ['url' => 'https://example.com/recipe']);
        $controller->__invoke($request);
    }

    public function testCategorizerResultIsIncludedEvenWhenEmpty(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn(
            $this->makeHttpResponse('<html><head><title>Test</title></head><body></body></html>')
        );

        $tagRepo = $this->createStub(TagRepository::class);
        $tagRepo->method('findAll')->willReturn([]);

        $ingredientRepo = $this->createStub(IngredientRepository::class);
        $ingredientRepo->method('findAll')->willReturn([]);

        $categorizer = $this->createStub(RecipeCategorizerInterface::class);
        $categorizer->method('categorize')->willReturn(['tags' => [], 'ingredients' => []]);

        $controller = new RecipeMetadataController($httpClient, new NullLogger(), $categorizer, $tagRepo, $ingredientRepo);

        $request = Request::create('/recipe/metadata', 'GET', ['url' => 'https://example.com/recipe']);
        $response = $controller->__invoke($request);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('ingredients', $data);
        $this->assertSame([], $data['tags']);
        $this->assertSame([], $data['ingredients']);
    }
}
