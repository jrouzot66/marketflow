<?php

namespace App\Infrastructure\Messenger\Message;

/**
 * Message : indexer une offre dans Elasticsearch
 * Transporté via RabbitMQ
 */
final class IndexOfferMessage
{
    public function __construct(
        public readonly string $offerId,
    ) {
    }
}
