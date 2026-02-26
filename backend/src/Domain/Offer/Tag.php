<?php

namespace App\Domain\Offer;

final class Tag
{
    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        $value = mb_strtolower($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Tag cannot be empty.');
        }

        if (mb_strlen($value) > 50) {
            throw new \InvalidArgumentException('Tag cannot exceed 50 characters.');
        }

        // Slug : alphanumeric + dash + underscore only
        if (!preg_match('/^[a-z0-9_-]+$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Tag "%s" contains invalid characters. Only alphanumeric, dash, underscore allowed.', $value));
        }

        $this->value = $value;
    }

    public static function fromString(string $rawValue): self
    {
        return new self($rawValue);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
