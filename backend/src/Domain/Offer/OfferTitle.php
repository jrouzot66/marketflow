<?php
// src/Domain/Offer/OfferTitle.php

namespace App\Domain\Offer;

final class OfferTitle
{
    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Offer title cannot be blank.');
        }

        if (mb_strlen($value) > 255) {
            throw new \InvalidArgumentException('Offer title cannot exceed 255 characters.');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
