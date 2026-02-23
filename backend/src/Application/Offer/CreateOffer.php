<?php

namespace App\Application\Offer;

use App\Application\Offer\Port\OfferRepositoryInterface;
use App\Application\Tenant\TenantContext;
use App\Entity\Offer;
use Symfony\Component\Uid\Ulid;

final class CreateOffer
{
    private OfferRepositoryInterface $offers;
    private TenantContext $tenantContext;

    public function __construct(OfferRepositoryInterface $offers, TenantContext $tenantContext)
    {
        $this->offers = $offers;
        $this->tenantContext = $tenantContext;
    }

    public function create(string $title): Offer
    {
        $tenantId = $this->tenantContext->getTenantId()->toRfc4122();

        $offer = new Offer(new Ulid(), $tenantId, $title);

        $this->offers->add($offer);

        return $offer;
    }
}
