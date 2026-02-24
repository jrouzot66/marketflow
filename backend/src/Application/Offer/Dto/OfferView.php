<?php
// src/Application/Offer/Dto/OfferView.php

namespace App\Application\Offer\Dto;

final class OfferView
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $title
    ) {
    }
}
