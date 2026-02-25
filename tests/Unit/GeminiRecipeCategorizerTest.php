<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\GeminiRecipeCategorizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GeminiRecipeCategorizerTest extends TestCase
{
    public function testReturnsEmptyArraysWhenApiKeyIsEmpty(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), '');

        $result = $categorizer->categorize('https://example.com/recipe', ['tag1'], ['ingredient1']);

        $this->assertSame(['tags' => [], 'ingredients' => []], $result);
    }

    public function testReturnsSuggestionsFromGeminiResponse(): void
    {
        $jsonResponse = json_encode(['tags' => ['Italiensk', 'Hurtig'], 'ingredients' => ['pasta', 'æg']]);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => $jsonResponse],
                        ],
                    ],
                ],
            ],
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');

        $result = $categorizer->categorize('https://example.com/carbonara', [], []);

        $this->assertSame(['Italiensk', 'Hurtig'], $result['tags']);
        $this->assertSame(['pasta', 'æg'], $result['ingredients']);
    }

    public function testReturnsEmptyArraysOnHttpFailure(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \RuntimeException('Connection failed'));

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');

        $result = $categorizer->categorize('https://example.com/recipe', [], []);

        $this->assertSame(['tags' => [], 'ingredients' => []], $result);
    }

    public function testReturnsEmptyArraysOnMalformedJsonResponse(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'this is not json'],
                        ],
                    ],
                ],
            ],
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');

        $result = $categorizer->categorize('https://example.com/recipe', [], []);

        $this->assertSame(['tags' => [], 'ingredients' => []], $result);
    }

    public function testReturnsEmptyArraysOnMissingCandidates(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');

        $result = $categorizer->categorize('https://example.com/recipe', [], []);

        $this->assertSame(['tags' => [], 'ingredients' => []], $result);
    }

    public function testUrlIsIncludedInPrompt(): void
    {
        $capturedOptions = null;

        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"tags":[],"ingredients":[]}']]],
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(function (array $options) use (&$capturedOptions) {
                    $capturedOptions = $options;
                    return true;
                })
            )
            ->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');
        $categorizer->categorize('https://example.com/pasta', [], []);

        $prompt = $capturedOptions['json']['contents'][0]['parts'][0]['text'];
        $this->assertStringContainsString('https://example.com/pasta', $prompt);
    }

    public function testExistingTagsAndIngredientsAreIncludedInPrompt(): void
    {
        $capturedPrompt = null;

        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"tags":[],"ingredients":[]}']]],
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $this->anything(), $this->callback(function (array $options) use (&$capturedPrompt) {
                $capturedPrompt = $options['json']['contents'][0]['parts'][0]['text'];
                return true;
            }))
            ->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');
        $categorizer->categorize('https://example.com/recipe', ['Hurtig', 'Vegetar'], ['Pasta', 'Tomater']);

        $this->assertStringContainsString('Hurtig', $capturedPrompt);
        $this->assertStringContainsString('Vegetar', $capturedPrompt);
        $this->assertStringContainsString('Pasta', $capturedPrompt);
        $this->assertStringContainsString('Tomater', $capturedPrompt);
    }

    public function testJsonResponseMimeTypeIsRequested(): void
    {
        $capturedOptions = null;

        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"tags":[],"ingredients":[]}']]],
            ]],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $this->anything(), $this->callback(function (array $options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            }))
            ->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');
        $categorizer->categorize('https://example.com/recipe', [], []);

        $this->assertSame('application/json', $capturedOptions['json']['generationConfig']['responseMimeType']);
    }

    public function testNonStringValuesInResponseAreFiltered(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'candidates' => [[
                'content' => ['parts' => [['text' => '{"tags":["Hurtig", 123, null, "Vegetar"],"ingredients":[true, "Pasta"]}']]],
            ]],
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $categorizer = new GeminiRecipeCategorizer($httpClient, new NullLogger(), 'test-api-key');
        $result = $categorizer->categorize('https://example.com/recipe', [], []);

        $this->assertSame(['Hurtig', 'Vegetar'], $result['tags']);
        $this->assertSame(['Pasta'], $result['ingredients']);
    }
}
