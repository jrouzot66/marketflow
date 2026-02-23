<?php

namespace App\Application\Tenant;

use Symfony\Component\Uid\Ulid;

final class TenantId
{
    private Ulid $value;

    private function __construct(Ulid $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self(Ulid::fromString($value));
    }

    public function toRfc4122(): string
    {
        return $this->value->toRfc4122();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
