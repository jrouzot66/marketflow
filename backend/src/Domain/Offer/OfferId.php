<?php

namespace App\Domain\Offer;

use Symfony\Component\Uid\Ulid;

final class OfferId
{
    private Ulid $value;

    private function __construct(Ulid $value)
    {
        $this->value = $value;
    }

    public static function new(): self
    {
        return new self(new Ulid());
    }

    public static function fromString(string $value): self
    {
        return new self(Ulid::fromString($value));
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }
}
