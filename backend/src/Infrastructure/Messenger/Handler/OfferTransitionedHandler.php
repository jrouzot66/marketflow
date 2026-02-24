<?php

namespace App\Infrastructure\Messenger\Handler;

use App\Application\Messaging\Message\OfferTransitioned;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OfferTransitionedHandler
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(OfferTransitioned $message): void
    {
        // Placeholder : plus tard on branchera Mailer, ES, etc.
        $this->logger->info('Offer transitioned', [
            'tenantId' => $message->tenantId,
            'offerId' => $message->offerId,
            'transition' => $message->transition,
            'toStatus' => $message->toStatus,
        ]);
    }
}
