<?php

namespace App\Application\Offer;

use App\Application\Offer\Dto\OfferView;
use App\Application\Tenant\TenantContext;
use App\Domain\Offer\Offer;
use App\Domain\Offer\OfferTitle;
use App\Domain\Tenant\TenantId as DomainTenantId;

final class CreateOffer
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OfferRepository $offers
    ) {
    }

    public function create(string $title): OfferView
    {
        $tenantId = DomainTenantId::fromString((string) $this->tenantContext->getTenantId());

        $offer = Offer::create(
            $tenantId,
            OfferTitle::fromString($title)
        );

        $this->offers->save($offer);

        return new OfferView(
            $offer->id()->toString(),
            $offer->tenantId()->toString(),
            $offer->title()->toString()
        );
    }
}
