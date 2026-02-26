<?php

namespace App\Application\Offer\Dto;

/**
 * DTO : résultat de recherche d'une offre
 */
final class SearchOfferResult
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly array $tags,
        public readonly string $status,
        public readonly float $score,
    ) {
    }
}
