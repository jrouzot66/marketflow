<?php

namespace App\Application\Offer;

use App\Application\Offer\Port\OfferRepositoryInterface;
use App\Entity\Offer;

final class ListOffers
{
    private OfferRepositoryInterface $offers;

    public function __construct(OfferRepositoryInterface $offers)
    {
        $this->offers = $offers;
    }

    /**
     * @return Offer[]
     */
    public function list(): array
    {
        return $this->offers->listAllOrderedByTitle();
    }
}
