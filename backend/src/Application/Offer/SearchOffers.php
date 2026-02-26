<?php

namespace App\Application\Offer;

use App\Application\Offer\Dto\SearchOfferFilters;
use App\Application\Tenant\TenantContext;
use App\Infrastructure\Search\SearchOfferRepository;

/**
 * Use-case : chercher les offres avec filtres
 * Normes DDD : orchestration + validation
 */
final class SearchOffers
{
    public function __construct(
        private readonly SearchOfferRepository $searchRepository,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * Cherche les offres du tenant courant
     * @return array{items: list, total: int, page: int, limit: int}
     */
    public function search(
        ?string $query = null,
        array $tags = [],
        ?string $status = null,
        int $page = 1,
        int $limit = 20,
        string $sortBy = '_score',
        string $sortOrder = 'desc'
    ): array {
        $filters = new SearchOfferFilters(
            tenantId: (string) $this->tenantContext->getTenantId(),
            query: $query,
            tags: $tags,
            status: $status,
            page: $page,
            limit: $limit,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
        );

        return $this->searchRepository->search($filters);
    }
}
