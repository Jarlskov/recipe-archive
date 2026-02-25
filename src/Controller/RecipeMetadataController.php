<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\IngredientRepository;
use App\Repository\TagRepository;
use App\Service\RecipeCategorizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/recipe/metadata', name: 'app_recipe_metadata', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class RecipeMetadataController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Target('recipe_metadata.logger')] private readonly LoggerInterface $logger,
        private readonly RecipeCategorizerInterface $categorizer,
        private readonly TagRepository $tagRepository,
        private readonly IngredientRepository $ingredientRepository,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $url = $request->query->get('url');

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => 'Invalid URL'], 400);
        }

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);
            $content = $response->getContent();

            $crawler = new Crawler($content);

            // Try to find title
            $title = $crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : '';
            if (empty($title)) {
                $title = $crawler->filter('meta[property="og:title"]')->count() > 0
                    ? $crawler->filter('meta[property="og:title"]')->attr('content')
                    : '';
            }

            // Try to find author
            $author = $crawler->filter('meta[name="author"]')->count() > 0
                ? $crawler->filter('meta[name="author"]')->attr('content')
                : '';
            if (empty($author)) {
                $author = $crawler->filter('meta[property="og:site_name"]')->count() > 0
                    ? $crawler->filter('meta[property="og:site_name"]')->attr('content')
                    : '';
            }

            $existingTagNames = array_map(
                fn ($tag) => (string) $tag->getName(),
                $this->tagRepository->findAll()
            );
            $existingIngredientNames = array_map(
                fn ($ingredient) => (string) $ingredient->getName(),
                $this->ingredientRepository->findAll()
            );

            $suggestions = $this->categorizer->categorize($url, $existingTagNames, $existingIngredientNames);

            return new JsonResponse([
                'title' => $title,
                'author' => $author,
                'tags' => $suggestions['tags'],
                'ingredients' => $suggestions['ingredients'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch recipe metadata', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse(['error' => 'Could not fetch metadata'], 500);
        }
    }
}
