<?php

namespace App\Domain\Common;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
