<?php

namespace App\Application\Offer\Port;

use App\Entity\Offer;

interface OfferRepositoryInterface
{
    public function add(Offer $offer): void;

    /**
     * @return Offer[]
     */
    public function listAllOrderedByTitle(): array;
}
