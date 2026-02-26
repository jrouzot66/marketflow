<?php

namespace App\Interface\Http\Controller;

use App\Application\Offer\SearchOffers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoints de recherche d'offres
 * Route SPÉCIFIQUE avant les routes génériques {id}
 */
#[Route('/api')]
final class OfferSearchController extends AbstractController
{
    public function __construct(
        private readonly SearchOffers $searchOffers,
    ) {
    }

    #[Route('/offers/search', name: 'app_offer_search', methods: ['GET'], priority: 10)]
    public function search(Request $request): JsonResponse
    {
        try {
            // Extraction SANS clipping (pour validation)
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);
            $query = $request->query->get('q');
            $status = $request->query->get('status');
            $sortBy = $request->query->get('sortBy', '_score');
            $sortOrder = $request->query->get('sortOrder', 'desc');

            // Tags
            $tagsParam = $request->query->get('tags');
            $tags = is_array($tagsParam) ? $tagsParam : (is_string($tagsParam) ? [$tagsParam] : []);
            $tags = array_filter($tags);

            // Appeler le use-case (qui validera)
            $result = $this->searchOffers->search(
                query: $query,
                tags: $tags,
                status: $status,
                page: $page,
                limit: $limit,
                sortBy: $sortBy,
                sortOrder: $sortOrder,
            );

            // Réponse
            return $this->json([
                'items' => array_map(fn ($item) => [
                    'id' => $item->id,
                    'tenantId' => $item->tenantId,
                    'title' => $item->title,
                    'description' => $item->description,
                    'tags' => $item->tags,
                    'status' => $item->status,
                    'score' => $item->score,
                ], $result['items']),
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'pages' => (int) ceil($result['total'] / $result['limit']),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('Search failed', ['error' => $e->getMessage()]);
            return $this->json(
                ['error' => 'Search failed'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
