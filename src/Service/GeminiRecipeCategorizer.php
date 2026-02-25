<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiRecipeCategorizer implements RecipeCategorizerInterface
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    /**
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     * @param string $geminiApiKey
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $geminiApiKey,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function categorize(string $url, array $existingTags, array $existingIngredients): array
    {
        if (empty($this->geminiApiKey)) {
            return ['tags' => [], 'ingredients' => []];
        }

        $prompt = $this->buildPrompt($url, $existingTags, $existingIngredients);

        try {
            $response = $this->httpClient->request('POST', self::GEMINI_API_URL, [
                'query' => ['key' => $this->geminiApiKey],
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ],
                ],
            ]);

            $data = $response->toArray();
            $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            /** @var array{tags?: string[], ingredients?: string[]} $result */
            $result = json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);

            return [
                'tags' => array_values(array_filter((array) ($result['tags'] ?? []), 'is_string')),
                'ingredients' => array_values(array_filter((array) ($result['ingredients'] ?? []), 'is_string')),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('GeminiRecipeCategorizer failed', [
                'error' => $e->getMessage(),
            ]);

            return ['tags' => [], 'ingredients' => []];
        }
    }

    /**
     * @param string $url
     * @param string[] $existingTags
     * @param string[] $existingIngredients
     * @return string
     */
    private function buildPrompt(string $url, array $existingTags, array $existingIngredients): string
    {
        $tagsLine = $existingTags ? implode(', ', $existingTags) : '(ingen)';
        $ingredientsLine = $existingIngredients ? implode(', ', $existingIngredients) : '(ingen)';

        return <<<PROMPT
Du kategoriserer en opskrift til et personligt opskriftsarkiv.

Besøg opskriftens URL og foreslå relevante tags og ingredienser baseret på opskriften.

Tags og ingredienser skal altid være på dansk, uanset om opskriften er på engelsk eller dansk.

Eksisterende tags som brugeren allerede bruger: {$tagsLine}
Eksisterende ingredienser som brugeren allerede bruger: {$ingredientsLine}

Brug eksisterende tags og ingredienser hvor det er passende. Du må foreslå nye hvis de er klart relevante og ikke allerede er dækket.

Returner KUN gyldigt JSON i dette præcise format:
{"tags": ["tag1", "tag2"], "ingredients": ["ingrediens1", "ingrediens2"]}

Opskriftens URL: {$url}
PROMPT;
    }
}
