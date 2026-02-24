<?php

namespace App\Application\Offer;

use App\Application\Offer\Dto\OfferView;
use App\Application\Tenant\TenantContext;
use App\Domain\Tenant\TenantId as DomainTenantId;

final class ListOffers
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OfferRepository $offers
    ) {
    }

    /**
     * @return list<OfferView>
     */
    public function list(): array
    {
        $tenantId = DomainTenantId::fromString((string) $this->tenantContext->getTenantId());

        $items = [];
        foreach ($this->offers->listByTenant($tenantId) as $offer) {
            $items[] = new OfferView(
                $offer->id()->toString(),
                $offer->tenantId()->toString(),
                $offer->title()->toString()
            );
        }

        return $items;
    }
}
