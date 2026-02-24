<?php

namespace App\Domain\Offer;

use App\Domain\Common\DomainEvent;
use App\Domain\Offer\Event\OfferCreated;
use App\Domain\Tenant\TenantId;

final class Offer
{
    /** @var list<DomainEvent> */
    private array $events = [];

    private function __construct(
        private readonly OfferId $id,
        private readonly TenantId $tenantId,
        private OfferTitle $title
    ) {
    }

    public static function create(TenantId $tenantId, OfferTitle $title): self
    {
        $offer = new self(OfferId::new(), $tenantId, $title);
        $offer->record(new OfferCreated($offer->id, $tenantId));

        return $offer;
    }

    public static function rehydrate(OfferId $id, TenantId $tenantId, OfferTitle $title): self
    {
        return new self($id, $tenantId, $title);
    }

    public function id(): OfferId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function title(): OfferTitle
    {
        return $this->title;
    }

    /** @return list<DomainEvent> */
    public function pullEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->events[] = $event;
    }
}
