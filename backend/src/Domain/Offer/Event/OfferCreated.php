<?php

namespace App\Domain\Offer\Event;

use App\Domain\Common\DomainEvent;
use App\Domain\Offer\OfferId;
use App\Domain\Tenant\TenantId;

final class OfferCreated implements DomainEvent
{
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        private readonly OfferId $offerId,
        private readonly TenantId $tenantId
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function offerId(): OfferId
    {
        return $this->offerId;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
