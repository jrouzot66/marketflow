<?php

namespace App\Application\Offer;

use App\Domain\Offer\OfferAggregate;
use App\Domain\Tenant\TenantId;

interface OfferRepository
{
    public function save(OfferAggregate $offer): void;

    /**
     * @return list<OfferAggregate>
     */
    public function listByTenant(TenantId $tenantId): array;
}
