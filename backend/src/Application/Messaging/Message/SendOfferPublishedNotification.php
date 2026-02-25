<?php

namespace App\Application\Messaging\Message;

final class SendOfferPublishedNotification
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $offerId,
        public readonly string $offerTitle
    ) {
    }
}
