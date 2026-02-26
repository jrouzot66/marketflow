<?php

namespace App\Application\Offer;

use App\Application\Offer\Dto\OfferView;
use App\Application\Tenant\TenantContext;
use App\Domain\Offer\OfferAggregate;
use App\Domain\Offer\OfferTitle;
use App\Domain\Tenant\TenantId as DomainTenantId;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateOffer
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly OfferRepository $offers,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(string $title): OfferView
    {
        $tenantId = DomainTenantId::fromString((string) $this->tenantContext->getTenantId());

        $offer = OfferAggregate::create(
            $tenantId,
            OfferTitle::fromString($title)
        );

        // Sauvegarder l'agrégat
        $this->offers->save($offer);

        // Dispatcher les events domaine
        $events = $offer->pullEvents();
        $this->logger->info('Domain events pulled', ['count' => count($events)]);

        foreach ($events as $event) {
            $this->logger->info('Dispatching event', ['event' => get_class($event)]);
            $this->dispatcher->dispatch($event);
        }

        return new OfferView(
            $offer->id()->toString(),
            $offer->tenantId()->toString(),
            $offer->title()->toString()
        );
    }
}
