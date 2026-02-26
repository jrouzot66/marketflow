<?php

namespace App\Infrastructure\Search;

use App\Application\Offer\Dto\SearchOfferFilters;
use App\Application\Offer\Dto\SearchOfferResult;
use Psr\Log\LoggerInterface;

/**
 * Repository de recherche (adaptateur Elasticsearch)
 * Normes DDD : convertit requêtes en queries ES, résultats en DTOs
 */
final class SearchOfferRepository
{
    public function __construct(
        private readonly ElasticsearchClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Cherche les offres avec filtres
     * @return array{items: list<SearchOfferResult>, total: int, page: int, limit: int}
     */
    public function search(SearchOfferFilters $filters): array
    {
        $query = $this->buildQuery($filters);
        $result = $this->client->search($filters->tenantId, $query);

        $items = array_map(
            fn (array $hit) => $this->hitToResult($hit),
            $result['hits']
        );

        return [
            'items' => $items,
            'total' => $result['total'],
            'page' => $filters->page,
            'limit' => $filters->limit,
        ];
    }

    /**
     * Construit une query Elasticsearch depuis les filtres
     * @return array<string, mixed>
     */
    /**
     * Construit une query Elasticsearch depuis les filtres
     * @return array<string, mixed>
     */
    private function buildQuery(SearchOfferFilters $filters): array
    {
        $must = [];
        $filter = [];

        // Full-text search : ajouter à 'must' seulement s'il y a une query
        if ($filters->query !== null && $filters->query !== '') {
            $must[] = [
                'multi_match' => [
                    'query' => $filters->query,
                    'fields' => ['title^2', 'description'],  // Donner plus de poids au title
                    'operator' => 'or',
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        // Filtrage par statut
        if ($filters->status !== null) {
            $filter[] = ['term' => ['status' => $filters->status]];
        }

        // Filtrage par tags (au moins un)
        if ($filters->tags !== []) {
            $filter[] = ['terms' => ['tags' => $filters->tags]];
        }

        // Pagination
        $from = ($filters->page - 1) * $filters->limit;

        // Tri
        $sortField = match ($filters->sortBy) {
            'title' => 'title.keyword',
            'status' => 'status',
            default => '_score',
        };

        return [
            'must' => $must,
            'filter' => $filter,
            'from' => $from,
            'size' => $filters->limit,
            'sort' => [[$sortField => ['order' => $filters->sortOrder]]],
        ];
    }

    /**
     * Convertit un hit ES en DTO
     */
    private function hitToResult(array $hit): SearchOfferResult
    {
        $source = $hit['_source'];

        return new SearchOfferResult(
            id: $hit['_id'],
            tenantId: $source['tenant_id'],
            title: $source['title'] ?? '',
            description: $source['description'] ?? null,
            tags: $source['tags'] ?? [],
            status: $source['status'] ?? 'draft',
            score: (float) ($hit['_score'] ?? 0.0),
        );
    }
}
