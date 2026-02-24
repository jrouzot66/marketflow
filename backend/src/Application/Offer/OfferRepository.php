<?php

namespace App\Application\Offer;

use App\Domain\Offer\Offer;
use App\Domain\Tenant\TenantId;

interface OfferRepository
{
    public function save(Offer $offer): void;

    /**
     * @return list<Offer>
     */
    public function listByTenant(TenantId $tenantId): array;
}
