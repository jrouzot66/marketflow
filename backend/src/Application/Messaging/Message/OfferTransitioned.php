<?php

namespace App\Application\Messaging\Message;

final class OfferTransitioned
{
    public function __construct(
        public readonly string $tenantId,   // ULID string (même valeur que X-Tenant)
        public readonly string $offerId,    // ULID string
        public readonly string $transition, // submit/approve/publish...
        public readonly string $toStatus    // review/approved/published...
    ) {
    }
}
