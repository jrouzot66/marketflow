<?php

namespace App\Application\Offer\Dto;

/**
 * DTO : paramètres de recherche validés
 * Normes : immuable, validation stricte
 */
final class SearchOfferFilters
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly ?string $query = null,
        public readonly array $tags = [],
        public readonly ?string $status = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly string $sortBy = '_score',
        public readonly string $sortOrder = 'desc',
    ) {
        // Validation stricte
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page must be >= 1');
        }
        if ($this->limit < 1 || $this->limit > 100) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100');
        }
        if ($this->sortOrder !== 'asc' && $this->sortOrder !== 'desc') {
            throw new \InvalidArgumentException('SortOrder must be asc or desc');
        }
    }
}
