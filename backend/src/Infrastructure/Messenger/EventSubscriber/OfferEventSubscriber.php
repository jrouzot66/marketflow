<?php

namespace App\Infrastructure\Messenger\EventSubscriber;

use App\Domain\Offer\Event\OfferCreated;
use App\Infrastructure\Messenger\Message\IndexOfferMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Souscripteur d'événements domaine
 * Quand une offre est créée → dispatcher message async pour indexation
 * Normes : découplage événementiel domain ↔ infrastructure
 */
final class OfferEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OfferCreated::class => 'onOfferCreated',
        ];
    }

    public function onOfferCreated(OfferCreated $event): void
    {
        $offerId = $event->offerId()->toString();

        $this->logger->info('OfferCreated event received, dispatching IndexOfferMessage', [
            'offerId' => $offerId,
        ]);

        // Dispatcher un message pour indexer asynchrone
        $this->messageBus->dispatch(new IndexOfferMessage($offerId));

        $this->logger->info('IndexOfferMessage dispatched', ['offerId' => $offerId]);
    }
}
